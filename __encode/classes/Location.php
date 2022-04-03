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


class Location
{
	var $path;

	//
	// Split a file path into array elements
	//
	public static function splitPath($dir)
	{
		$dir = stripslashes($dir);
		$path1 = preg_split("/[\\\\\/]+/", $dir);
		$path2 = array();
		for($i = 0; $i < count($path1); $i++)
		{
			if($path1[$i] == ".." || $path1[$i] == "." || $path1[$i] == "")
				continue;
			$path2[] = $path1[$i];
		}
		return $path2;
	}

	//
	// Get the current directory.
	// Options: Include the prefix ("./"); URL-encode the string; HTML-encode the string; return directory n-levels up
	//
	function getDir($prefix, $encoded, $html, $up)
	{
		$dir = "";
		if($prefix == true)
			$dir .= "./";
		for($i = 0; $i < ((count($this->path) >= $up && $up > 0)?count($this->path)-$up:count($this->path)); $i++)
		{
			$temp = $this->path[$i];
			if($encoded)
				$temp = rawurlencode($temp);
			if($html)
				$temp = htmlspecialchars($temp);
			$dir .= $temp."/";
		}
		return $dir;
	}

	function getPathLink($i, $html)
	{
		if($html)
			return htmlspecialchars($this->path[$i]);
		else
			return $this->path[$i];
	}

	function getFullPath()
	{
		return (strlen(EncodeExplorer::getConfig('basedir')) > 0?EncodeExplorer::getConfig('basedir'):dirname($_SERVER['SCRIPT_FILENAME']))."/".$this->getDir(true, false, false, 0);
	}

	//
	// Debugging output
	//
	function debug()
	{
		print_r($this->path);
		print("Dir with prefix: ".$this->getDir(true, false, false, 0)."\n");
		print("Dir without prefix: ".$this->getDir(false, false, false, 0)."\n");
		print("Upper dir with prefix: ".$this->getDir(true, false, false, 1)."\n");
		print("Upper dir without prefix: ".$this->getDir(false, false, false, 1)."\n");
	}


	//
	// Set the current directory
	//
	function init()
	{
		if(!isset($_GET['dir']) || !is_scalar($_GET['dir']) || strlen($_GET['dir']) == 0)
		{
			$this->path = $this->splitPath(EncodeExplorer::getConfig('starting_dir'));
		}
		else
		{
			$this->path = $this->splitPath($_GET['dir']);
		}
	}

	//
	// Checks if the current directory is below the input path
	//
	function isSubDir($checkPath)
	{
		for($i = 0; $i < count($this->path); $i++)
		{
			if(strcmp($this->getDir(true, false, false, $i), $checkPath) == 0)
				return true;
		}
		return false;
	}

	//
	// Check if uploading is allowed into the current directory, based on the configuration
	//
	function uploadAllowed()
	{
		if(EncodeExplorer::getConfig('upload_enable') != true)
			return false;
		if(EncodeExplorer::getConfig('upload_dirs') == null || count(EncodeExplorer::getConfig('upload_dirs')) == 0)
			return true;

		$upload_dirs = EncodeExplorer::getConfig('upload_dirs');
		for($i = 0; $i < count($upload_dirs); $i++)
		{
			if($this->isSubDir($upload_dirs[$i]))
				return true;
		}
		return false;
	}

	function isWritable()
	{
		return is_writable($this->getDir(true, false, false, 0));
	}

	public static function isDirWritable($dir)
	{
		return is_writable($dir);
	}

	public static function isFileWritable($file)
	{
		if(file_exists($file))
		{
			if(is_writable($file))
				return true;
			else
				return false;
		}
		else if(Location::isDirWritable(dirname($file)))
			return true;
		else
			return false;
	}
}

?>
