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


//
// The class for logging user activity
//


class Logger
{
	public static function log($message)
	{
		global $encodeExplorer;
		if(strlen(EncodeExplorer::getConfig('log_file')) > 0)
		{
			if(Location::isFileWritable(EncodeExplorer::getConfig('log_file')))
			{
				$message = "[" . date("Y-m-d h:i:s", mktime()) . "] ".$message." (".$_SERVER["HTTP_USER_AGENT"].")\n";
				error_log($message, 3, EncodeExplorer::getConfig('log_file'));
			}
			else
				$encodeExplorer->setErrorString("log_file_permission_error");
		}
	}

	public static function logAccess($path, $isDir)
	{
		$message = $_SERVER['REMOTE_ADDR']." ".GateKeeper::getUserName()." accessed ";
		$message .= $isDir?"dir":"file";
		$message .= " ".$path;
		Logger::log($message);
	}

	public static function logQuery()
	{
		if(isset($_POST['log']) && strlen($_POST['log']) > 0)
		{
			Logger::logAccess($_POST['log'], false);
			return true;
		}
		else
			return false;
	}

	public static function logCreation($path, $isDir)
	{
		$message = $_SERVER['REMOTE_ADDR']." ".GateKeeper::getUserName()." created ";
		$message .= $isDir?"dir":"file";
		$message .= " ".$path;
		Logger::log($message);
	}

	public static function emailNotification($path, $isFile)
	{
		if(strlen(EncodeExplorer::getConfig('upload_email')) > 0)
		{
			$message = "This is a message to let you know that ".GateKeeper::getUserName()." ";
			$message .= ($isFile?"uploaded a new file":"created a new directory")." in Encode Explorer.\n\n";
			$message .= "Path : ".$path."\n";
			$message .= "IP : ".$_SERVER['REMOTE_ADDR']."\n";
			mail(EncodeExplorer::getConfig('upload_email'), "Upload notification", $message);
		}
	}
}

?>
