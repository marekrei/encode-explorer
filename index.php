<?php
/***************************************************************************
 *
 * Encode Explorer
 *
 * Author : Marek Rei (marek ät marekrei dot com)
 * Version : 6.4.1
 * Homepage : encode-explorer.siineiolekala.net
 *
 *
 * NB!:If you change anything, save with UTF-8! Otherwise you may
 *     encounter problems, especially when displaying images.
 *
 ***************************************************************************/

include '__encode/config.php';
include '__encode/translations.php';
include '__encode/images.php';


/***************************************************************************/
/*   HERE COMES THE CODE.                                                  */
/*   DON'T CHANGE UNLESS YOU KNOW WHAT YOU ARE DOING ;)                    */
/***************************************************************************/

include '__encode/classes/ImageServer.php';
include '__encode/classes/Logger.php';
include '__encode/classes/GateKeeper.php';
include '__encode/classes/FileManager.php';
include '__encode/classes/Dir.php';
include '__encode/classes/File.php';
include '__encode/classes/Location.php';


class EncodeExplorer
{
	var $location;
	var $dirs;
	var $files;
	var $sort_by;
	var $sort_as;
	var $mobile;
	var $logging;
	var $spaceUsed;
	var $lang;

	//
	// Determine sorting, calculate space.
	//
	function init()
	{
		global $_TRANSLATIONS;

		// Here we filter the comparison function (sort by) and comparison order (sort as) chosen by user
		$this->sort_by = (isset($_GET['sort_by']) && in_array($_GET['sort_by'], array('name', 'size', 'mod'))) ? $_GET['sort_by'] : 'name';
		$this->sort_as = (isset($_GET['sort_as']) && in_array($_GET['sort_as'], array('asc', 'desc'))) ? $_GET['sort_as'] : 'asc';

		// Mitigate date.timezone warning
		if(function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set'))
		{
			@date_default_timezone_set(date_default_timezone_get());
		}

		if(isset($_GET['lang']) && is_scalar($_GET['lang']) && isset($_TRANSLATIONS[$_GET['lang']]))
			$this->lang = $_GET['lang'];
		else
			$this->lang = EncodeExplorer::getConfig("lang");

		$this->mobile = false;
		if(EncodeExplorer::getConfig("mobile_enabled") == true)
		{
			if((EncodeExplorer::getConfig("mobile_default") == true || isset($_GET['m'])) && !isset($_GET['s']))
				$this->mobile = true;
		}

		$this->logging = false;
		if(EncodeExplorer::getConfig("log_file") != null && strlen(EncodeExplorer::getConfig("log_file")) > 0)
			$this->logging = true;
	}

	//
	// Read the file list from the directory
	//
	function readDir()
	{
		global $encodeExplorer;
		//
		// Reading the data of files and directories
		//
		if($open_dir = @opendir($this->location->getFullPath()))
		{
			$this->dirs = array();
			$this->files = array();
			while (false !== ($object = readdir($open_dir)))
			{
				if($object != "." && $object != "..")
				{
					if(is_dir($this->location->getDir(true, false, false, 0)."/".$object))
					{
						if(!in_array($object, EncodeExplorer::getConfig('hidden_dirs')))
							$this->dirs[] = new Dir($object, $this->location);
					}
					else if(!in_array($object, EncodeExplorer::getConfig('hidden_files')))
						$this->files[] = new File($object, $this->location);
				}
			}
			closedir($open_dir);
		}
		else
		{
			$encodeExplorer->setErrorString("unable_to_read_dir");;
		}
	}

	//
	// A recursive function for calculating the total used space
	//
	function sum_dir($start_dir, $ignore_files, $levels = 1)
	{
		if ($dir = opendir($start_dir))
		{
			$total = 0;
			while (false !== ($file = readdir($dir)))
			{
				if (!in_array($file, $ignore_files))
				{
					if ((is_dir($start_dir . '/' . $file)) && ($levels - 1 >= 0))
					{
						$total += $this->sum_dir($start_dir . '/' . $file, $ignore_files, $levels-1);
					}
					elseif (is_file($start_dir . '/' . $file))
					{
						$total += File::getFileSize($start_dir . '/' . $file) / 1024;
					}
				}
			}

			closedir($dir);
			return $total;
		}
	}

	function calculateSpace()
	{
		if(EncodeExplorer::getConfig('calculate_space_level') <= 0)
			return;
		$ignore_files = array('..', '.');
		$start_dir = getcwd();
		$spaceUsed = $this->sum_dir($start_dir, $ignore_files, EncodeExplorer::getConfig('calculate_space_level'));
		$this->spaceUsed = round($spaceUsed/1024, 3);
	}

	function sort()
	{
		// Here we filter the comparison functions supported by our directory object
		$sort_by = in_array($this->sort_by, array('name', 'mod')) ? $this->sort_by : 'name';

		if(is_array($this->dirs)) {
			usort($this->dirs, array('EncodeExplorer', 'cmp_'.$sort_by));
			if($this->sort_as == "desc") {
				$this->dirs = array_reverse($this->dirs);
			}
		}

		// Here we filter the comparison functions supported by our file object
		$sort_by = in_array($this->sort_by, array('name', 'size', 'mod')) ? $this->sort_by : 'name';

		if(is_array($this->files)) {
			usort($this->files, array('EncodeExplorer', 'cmp_'.$sort_by));
			if($this->sort_as == "desc") {
				$this->files = array_reverse($this->files);
			}
		}
	}

	function makeArrow($sort_by)
	{
		// Ability to reverse the 'sort as' selected for the current field
		// And propagate the current selected 'sort as' to the other fields
		$sort_as = ($this->sort_as == "asc") ? "desc" : "asc";
		$sort_as = ($this->sort_by == $sort_by) ? $sort_as : $this->sort_as;

		// Only show image for the currently selected 'sort as' field
		$img = ($this->sort_as == "asc") ? "arrow_up" : "arrow_down";
		$img = ($this->sort_by == $sort_by) ? "<img style=\"border:0;\" alt=\"".$sort_as."\" src=\"?img=".$img."\" />" : "&nbsp;";

		if($sort_by == "name")
			$text = $this->getString("file_name");
		else if($sort_by == "size")
			$text = $this->getString("size");
		else if($sort_by == "mod")
			$text = $this->getString("last_changed");

		return "<a href=\"".$this->makeLink(false, false, $sort_by, $sort_as, null, $this->location->getDir(false, true, false, 0))."\">{$text}{$img}</a>";
	}

	function makeLink($switchVersion, $logout, $sort_by, $sort_as, $delete, $dir)
	{
		$link = "?";
		if($switchVersion == true && EncodeExplorer::getConfig("mobile_enabled") == true)
		{
			if($this->mobile == false)
				$link .= "m&amp;";
			else
				$link .= "s&amp;";
		}
		else if($this->mobile == true && EncodeExplorer::getConfig("mobile_enabled") == true && EncodeExplorer::getConfig("mobile_default") == false)
			$link .= "m&amp;";
		else if($this->mobile == false && EncodeExplorer::getConfig("mobile_enabled") == true && EncodeExplorer::getConfig("mobile_default") == true)
			$link .= "s&amp;";

		if($logout == true)
		{
			$link .= "logout";
			return $link;
		}

		if(isset($this->lang) && $this->lang != EncodeExplorer::getConfig("lang"))
			$link .= "lang=".$this->lang."&amp;";

		if($sort_by != null && strlen($sort_by) > 0)
			$link .= "sort_by=".$sort_by."&amp;";

		if($sort_as != null && strlen($sort_as) > 0)
			$link .= "sort_as=".$sort_as."&amp;";

		$link .= "dir=".$dir;
		if($delete != null)
			$link .= "&amp;del=".$delete;
		return $link;
	}

	function makeIcon($l)
	{
		$l = strtolower($l);
		return "?img=".$l;
	}

	function formatModTime($time)
	{
		$timeformat = "d.m.y H:i:s";
		if(EncodeExplorer::getConfig("time_format") != null && strlen(EncodeExplorer::getConfig("time_format")) > 0)
			$timeformat = EncodeExplorer::getConfig("time_format");
		return date($timeformat, $time);
	}

	function formatSize($size)
	{
		$sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB');
		$y = $sizes[0];
		for ($i = 1; (($i < count($sizes)) && ($size >= 1024)); $i++)
		{
			$size = $size / 1024;
			$y  = $sizes[$i];
		}
		return round($size, 2)." ".$y;
	}

	//
	// Debugging output
	//
	function debug()
	{
		print("Explorer location: ".$this->location->getDir(true, false, false, 0)."\n");
		for($i = 0; $i < count($this->dirs); $i++)
			$this->dirs[$i]->output();
		for($i = 0; $i < count($this->files); $i++)
			$this->files[$i]->output();
	}

	//
	// Comparison functions for sorting.
	//

	public static function cmp_name($a, $b)
	{
		return strcasecmp($a->name, $b->name);
	}

	public static function cmp_size($a, $b)
	{
		return ($a->size - $b->size);
	}

	public static function cmp_mod($a, $b)
	{
		return ($a->modTime - $b->modTime);
	}

	//
	// The function for getting a translated string.
	// Falls back to english if the correct language is missing something.
	//
	public static function getLangString($stringName, $lang)
	{
		global $_TRANSLATIONS;
		if(isset($_TRANSLATIONS[$lang]) && is_array($_TRANSLATIONS[$lang])
			&& isset($_TRANSLATIONS[$lang][$stringName]))
			return $_TRANSLATIONS[$lang][$stringName];
		else if(isset($_TRANSLATIONS["en"]))// && is_array($_TRANSLATIONS["en"])
			//&& isset($_TRANSLATIONS["en"][$stringName]))
			return $_TRANSLATIONS["en"][$stringName];
		else
			return "Translation error";
	}

	function getString($stringName)
	{
		return EncodeExplorer::getLangString($stringName, $this->lang);
	}

	//
	// The function for getting configuration values
	//
	public static function getConfig($name)
	{
		global $_CONFIG;
		if(isset($_CONFIG, $_CONFIG[$name]))
			return $_CONFIG[$name];
		return null;
	}

	public static function setError($message)
	{
		global $_ERROR;
		if(isset($_ERROR) && strlen($_ERROR) > 0)
			;// keep the first error and discard the rest
		else
			$_ERROR = $message;
	}

	function setErrorString($stringName)
	{
		EncodeExplorer::setError($this->getString($stringName));
	}

	//
	// Main function, activating tasks
	//
	function run($location)
	{
		$this->location = $location;
		$this->calculateSpace();
		$this->readDir();
		$this->sort();
		$this->outputHtml();
	}

	//
	// Refresh current page
	//
	public static function refresh()
	{
		$addr  = $_SERVER['PHP_SELF'];
		$param = '';

		if(isset($_GET['m']))
			$param .= (strlen($param) == 0 ? '?m' : '&m');

		if(isset($_GET['s']))
			$param .= (strlen($param) == 0 ? '?s' : '&s');

		if(isset($_GET['dir']) && strlen($_GET['dir']) > 0)
		{
			$param .= (strlen($param) == 0 ? '?dir=' : '&dir=');
			$param .= urlencode($_GET['dir']);
		}
		header( "Location: ".$addr.$param);
	}

	public function printLoginBox()
	{
		?>
		<div id="login">
		<form enctype="multipart/form-data" action="<?php print $this->makeLink(false, false, null, null, null, ""); ?>" method="post">
		<?php
		if(GateKeeper::isLoginRequired())
		{
			$require_username = false;
			foreach(EncodeExplorer::getConfig("users") as $user){
				if($user[0] != null && strlen($user[0]) > 0){
					$require_username = true;
					break;
				}
			}
			if($require_username)
			{
			?>
			<div><label for="user_name"><?php print $this->getString("username"); ?>:</label>
			<input type="text" name="user_name" value="" id="user_name" /></div>
			<?php
			}
			?>
			<div><label for="user_pass"><?php print $this->getString("password"); ?>:</label>
			<input type="password" name="user_pass" id="user_pass" /></div>
			<div><input type="submit" value="<?php print $this->getString("log_in"); ?>" class="button" /></div>
		</form>
		</div>
	<?php
		}
	}

	//
	// Printing the actual page
	//
	function outputHtml()
	{
		global $_ERROR;
		global $_START_TIME;
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $this->getConfig('lang'); ?>" lang="<?php print $this->getConfig('lang'); ?>">
<head>
<meta name="viewport" content="width=device-width" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php print $this->getConfig('charset'); ?>">
<link rel="stylesheet" type="text/css" href="__encode/static/encode.css">
<!-- <meta charset="<?php print $this->getConfig('charset'); ?>" /> -->

<?php
if($this->getConfig('jquery_source') != null && $this->getConfig('jquery_source') != "none")
{
	if(($this->getConfig('log_file') != null && strlen($this->getConfig('log_file')) > 0)
		|| ($this->getConfig('thumbnails') != null && $this->getConfig('thumbnails') == true && $this->mobile == false)
		|| (GateKeeper::isDeleteAllowed()))
	{

		//
		// Jquery source selection
		//
		$jq_src = "";
		if ($this->getConfig('jquery_source') == "local")
		{
			$jq_src = '__encode/static/jquery.min.js';
		}
		elseif ($this->getConfig('jquery_source') == "cdn")
		{
			$jq_src = $this->getConfig('jquery_cdn_url');
		}
		else {} // Do nothing


		// begin JS code
		echo "<script type=\"text/javascript\" src=\"" . $jq_src . "\"></script>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "//<![CDATA[\n";
		echo "$(document).ready(function() {\n";


		if(GateKeeper::isDeleteAllowed())
		{

			echo "
	$('td.del a').click(function(){
		var answer = confirm('Are you sure you want to delete : \"' + $(this).attr(\"data-name\") + '\" ?');
		return answer;
	});
";

		}

		if($this->logging == true)
		{
			echo "
	function logFileClick(path)
	{
		$.ajax({
			async: false,
			type: \"POST\",
			data: {log: path},
			contentType: \"application/x-www-form-urlencoded; charset=UTF-8\",
			cache: false
		});
	}

	$('a.file').click(function(){
		logFileClick('" . $this->location->getDir(true, true, false, 0) . "' + $(this).html());
		return true;
	});
";
		}

		if(EncodeExplorer::getConfig("thumbnails") == true && $this->mobile == false)
		{
			echo "
	function positionThumbnail(e) {
		xOffset = 30;
		yOffset = 10;
		$('#thumb').css(\"left\",(e.clientX + xOffset) + \"px\");

		diff = 0;
		if(e.clientY + $('#thumb').height() > $(window).height())
			diff = e.clientY + $('#thumb').height() - $(window).height();

		$('#thumb').css(\"top\",(e.pageY - yOffset - diff) + \"px\");
	}

	$('a.thumb').hover(function(e){
		$('#thumb').remove();
		$('body').append('<div id=\"thumb\"><img src=\"?thumb='+ $(this).attr(\"href\") +'\" alt=\"Preview\" \/><\/div>');
		positionThumbnail(e);
		$('#thumb').fadeIn(\"medium\");
	},
	function() { $('#thumb').remove(); });

	$('a.thumb').mousemove(function(e) { positionThumbnail(e); });
	$('a.thumb').click(function(e) { $('#thumb').remove(); return true;} );
";
		}


		// End of JS code
		echo "\n});\n//]]>\n</script>\n";
	}
}
?>

<title><?php if(EncodeExplorer::getConfig('main_title') != null) print EncodeExplorer::getConfig('main_title'); ?></title>
</head>
<body class="<?php print ($this->mobile == true?"mobile":"standard");?>">
<?php
//
// Print the error (if there is something to print)
//
if(isset($_ERROR) && strlen($_ERROR) > 0)
{
	print "<div id=\"error\">".$_ERROR."</div>";
}
?>
<div id="frame">
<?php
if(EncodeExplorer::getConfig('show_top') == true)
{
?>
<div id="top">
	<a href="<?php print $this->makeLink(false, false, null, null, null, ""); ?>"><span><?php if(EncodeExplorer::getConfig('main_title') != null) print EncodeExplorer::getConfig('main_title'); ?></span></a>
<?php
if(EncodeExplorer::getConfig("secondary_titles") != null && is_array(EncodeExplorer::getConfig("secondary_titles")) && count(EncodeExplorer::getConfig("secondary_titles")) > 0 && $this->mobile == false)
{
	$secondary_titles = EncodeExplorer::getConfig("secondary_titles");
	print "<div class=\"subtitle\">".$secondary_titles[array_rand($secondary_titles)]."</div>\n";
}
?>
</div>
<?php
}

// Checking if the user is allowed to access the page, otherwise showing the login box
if(!GateKeeper::isAccessAllowed())
{
	$this->printLoginBox();
}
else
{
if($this->mobile == false && EncodeExplorer::getConfig("show_path") == true)
{
?>
<div class="breadcrumbs">
<a href="?dir="><?php print $this->getString("root"); ?></a>
<?php
	for($i = 0; $i < count($this->location->path); $i++)
	{
		print "&gt; <a href=\"".$this->makeLink(false, false, null, null, null, $this->location->getDir(false, true, false, count($this->location->path) - $i - 1))."\">";
		print $this->location->getPathLink($i, true);
		print "</a>\n";
	}
?>
</div>
<?php
}
?>

<!-- START: List table -->
<table class="table">
<?php
if($this->mobile == false)
{
?>
<tr class="row one header">
	<td class="icon"> </td>
	<td class="name"><?php print $this->makeArrow("name");?></td>
	<td class="size"><?php print $this->makeArrow("size"); ?></td>
	<td class="changed"><?php print $this->makeArrow("mod"); ?></td>
	<?php if($this->mobile == false && GateKeeper::isDeleteAllowed()){?>
	<td class="del"><?php print EncodeExplorer::getString("del"); ?></td>
	<?php } ?>
</tr>
<?php
}
?>
<tr class="row two">
	<td class="icon"><img alt="dir" src="?img=directory" /></td>
	<td colspan="<?php print (($this->mobile == true?1:(GateKeeper::isDeleteAllowed()?4:3))); ?>" class="long">
		<a class="item" href="<?php print $this->makeLink(false, false, null, null, null, $this->location->getDir(false, true, false, 1)); ?>">..</a>
	</td>
</tr>
<?php
//
// Ready to display folders and files.
//
$row = 1;

//
// Folders first
//
if($this->dirs)
{
	foreach ($this->dirs as $dir)
	{
		$row_style = ($row ? "one" : "two");
		print "<tr class=\"row ".$row_style."\">\n";
		print "<td class=\"icon\"><img alt=\"dir\" src=\"?img=directory\" /></td>\n";
		print "<td class=\"name\" colspan=\"".($this->mobile == true ? 1:2)."\">\n";
		print "<a href=\"".$this->makeLink(false, false, null, null, null, $this->location->getDir(false, true, false, 0).$dir->getNameEncoded())."\" class=\"item dir\">";
		print $dir->getNameHtml();
		print "</a>\n";
		print "</td>\n";
		if($this->mobile != true)
		{
			print "<td class=\"changed\">".$this->formatModTime($dir->getModTime())."</td>\n";
		}
		if($this->mobile == false && GateKeeper::isDeleteAllowed())
		{
			print "<td class=\"del\"><a data-name=\"".htmlentities($dir->getName())."\" href=\"".$this->makeLink(false, false, null, null, $this->location->getDir(false, true, false, 0).$dir->getNameEncoded(), $this->location->getDir(false, true, false, 0))."\"><img src=\"?img=del\" alt=\"Delete\" /></a></td>";
		}
		print "</tr>\n";
		$row =! $row;
	}
}

//
// Now the files
//
if($this->files)
{
	$count = 0;
	foreach ($this->files as $file)
	{
		$row_style = ($row ? "one" : "two");
		print "<tr class=\"row ".$row_style.(++$count == count($this->files)?" last":"")."\">\n";
		print "<td class=\"icon\"><img alt=\"".$file->getType()."\" src=\"".$this->makeIcon($file->getType())."\" /></td>\n";
		print "<td class=\"name\" colspan=\"1\">\n";
		print "\t\t<a href=\"".$this->location->getDir(false, true, false, 0).$file->getNameEncoded()."\"";
		if(EncodeExplorer::getConfig('open_in_new_window') == true)
			print "target=\"_blank\"";
		print " class=\"item file";
		if($file->isValidForThumb())
			print " thumb";
		print "\">";
		print $file->getNameHtml();
		if($this->mobile == true)
		{
			print "<span class =\"size\">".$this->formatSize($file->getSize())."</span>";
		}
		print "</a>\n";
		print "</td>\n";
		if($this->mobile != true)
		{
			print "<td class=\"size\">".$this->formatSize($file->getSize())."</td>\n";
			print "<td class=\"changed\">".$this->formatModTime($file->getModTime())."</td>\n";
		}
		if($this->mobile == false && GateKeeper::isDeleteAllowed())
		{
			print "<td class=\"del\">
				<a data-name=\"".htmlentities($file->getName())."\" href=\"".$this->makeLink(false, false, null, null, $this->location->getDir(false, true, false, 0).$file->getNameEncoded(), $this->location->getDir(false, true, false, 0))."\">
					<img src=\"?img=del\" alt=\"Delete\" />
				</a>
			</td>";
		}
		print "</tr>\n";
		$row =! $row;
	}
}


//
// The files and folders have been displayed
//
?>

</table>
<!-- END: List table -->
<?php
}
?>
</div>

<?php
if(GateKeeper::isAccessAllowed() && GateKeeper::showLoginBox()){
?>
<!-- START: Login area -->
<form enctype="multipart/form-data" method="post">
	<div id="login_bar">
	<?php print $this->getString("username"); ?>:
	<input type="text" name="user_name" value="" id="user_name" />
	<?php print $this->getString("password"); ?>:
	<input type="password" name="user_pass" id="user_pass" />
	<input type="submit" class="submit" value="<?php print $this->getString("log_in"); ?>" />
	<div class="bar"></div>
	</div>
</form>
<!-- END: Login area -->
<?php
}

if(GateKeeper::isAccessAllowed() && $this->location->uploadAllowed() && (GateKeeper::isUploadAllowed() || GateKeeper::isNewdirAllowed()))
{
?>
<!-- START: Upload area -->
<form enctype="multipart/form-data" method="post">
	<div id="upload">
		<?php
		if(GateKeeper::isNewdirAllowed()){
		?>
		<div id="newdir_container">
			<input name="userdir" type="text" class="upload_dirname" />
			<input type="submit" value="<?php print $this->getString("make_directory"); ?>" />
		</div>
		<?php
		}
		if(GateKeeper::isUploadAllowed()){
		?>
		<div id="upload_container">
			<input name="userfile[]" type="file" multiple class="upload_file" />
			<input type="submit" value="<?php print $this->getString("upload"); ?>" class="upload_sumbit" />
		</div>
		<?php
		}
		?>
		<div class="bar"></div>
	</div>
</form>
<!-- END: Upload area -->
<?php
}

?>
<!-- START: Info area -->
<div id="info">
<?php
if(GateKeeper::isUserLoggedIn())
	print "<a href=\"".$this->makeLink(false, true, null, null, null, "")."\">".$this->getString("log_out")."</a> | ";

if(EncodeExplorer::getConfig("mobile_enabled") == true)
{
	print "<a href=\"".$this->makeLink(true, false, null, null, null, $this->location->getDir(false, true, false, 0))."\">\n";
	print ($this->mobile == true)?$this->getString("standard_version"):$this->getString("mobile_version")."\n";
	print "</a> | \n";
}
if(GateKeeper::isAccessAllowed() && $this->getConfig("calculate_space_level") > 0 && $this->mobile == false)
{
	print $this->getString("total_used_space").": ".$this->spaceUsed." MB | ";
}
if($this->mobile == false && $this->getConfig("show_load_time") == true)
{
	printf($this->getString("page_load_time")." | ", (microtime(TRUE) - $_START_TIME)*1000);
}
?>
<a href="http://encode-explorer.siineiolekala.net">Encode Explorer</a>
</div>
<!-- END: Info area -->
</body>
</html>

<?php
	}
}

//
// This is where the system is activated.
// We check if the user wants an image and show it. If not, we show the explorer.
//
$encodeExplorer = new EncodeExplorer();
$encodeExplorer->init();

GateKeeper::init();

if(!ImageServer::showImage() && !Logger::logQuery())
{
	$location = new Location();
	$location->init();
	if(GateKeeper::isAccessAllowed())
	{
		Logger::logAccess($location->getDir(true, false, false, 0), true);
		$fileManager = new FileManager();
		$fileManager->run($location);
	}
	$encodeExplorer->run($location);
}
