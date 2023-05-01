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
// The class for any kind of file managing (new folder, upload, etc).
//


class FileManager
{
	/* Obsolete code
	function checkPassword($inputPassword)
	{
		global $encodeExplorer;
		if(strlen(EncodeExplorer::getConfig("upload_password")) > 0 && $inputPassword == EncodeExplorer::getConfig("upload_password"))
		{
			return true;
		}
		else
		{
			$encodeExplorer->setErrorString("wrong_password");
			return false;
		}
	}
	*/
	function newFolder($location, $dirname)
	{
		global $encodeExplorer;
		if(strlen($dirname) > 0)
		{
			$forbidden = array(".", "/", "\\");
			for($i = 0; $i < count($forbidden); $i++)
			{
				$dirname = str_replace($forbidden[$i], "", $dirname);
			}
			if(!$location->uploadAllowed())
			{
				// The system configuration does not allow uploading here
				$encodeExplorer->setErrorString("upload_not_allowed");
			}
			else if(!$location->isWritable())
			{
				// The target directory is not writable
				$encodeExplorer->setErrorString("upload_dir_not_writable");
			}
			else if(!mkdir($location->getDir(true, false, false, 0).$dirname, EncodeExplorer::getConfig("new_dir_mode")))
			{
				// Error creating a new directory
				$encodeExplorer->setErrorString("new_dir_failed");
			}
			else if(!chmod($location->getDir(true, false, false, 0).$dirname, EncodeExplorer::getConfig("new_dir_mode")))
			{
				// Error applying chmod
				$encodeExplorer->setErrorString("chmod_dir_failed");
			}
			else
			{
				// Directory successfully created, sending e-mail notification
				Logger::logCreation($location->getDir(true, false, false, 0).$dirname, true);
				Logger::emailNotification($location->getDir(true, false, false, 0).$dirname, false);
			}
		}
	}

	function uploadFile($location, $userfile)
	{
		global $encodeExplorer;
		$name = basename($userfile['name']);

		$upload_dir = $location->getFullPath();
		$upload_file = $upload_dir . $name;

		if(function_exists("finfo_open") && function_exists("finfo_file"))
			$mime_type = File::getFileMime($userfile['tmp_name']);
		else
			$mime_type = $userfile['type'];

		$extension = File::getFileExtension($userfile['name']);

		if(!$location->uploadAllowed())
		{
			$encodeExplorer->setErrorString("upload_not_allowed");
		}
		else if(!$location->isWritable())
		{
			$encodeExplorer->setErrorString("upload_dir_not_writable");
		}
		else if(!is_uploaded_file($userfile['tmp_name']))
		{
			$encodeExplorer->setErrorString("failed_upload");
		}
		else if(is_array(EncodeExplorer::getConfig("upload_allow_type")) && count(EncodeExplorer::getConfig("upload_allow_type")) > 0 && !in_array($mime_type, EncodeExplorer::getConfig("upload_allow_type")))
		{
			$encodeExplorer->setErrorString("upload_type_not_allowed");
		}
		else if(is_array(EncodeExplorer::getConfig("upload_reject_extension")) && count(EncodeExplorer::getConfig("upload_reject_extension")) > 0 && in_array($extension, EncodeExplorer::getConfig("upload_reject_extension")))
		{
			$encodeExplorer->setErrorString("upload_type_not_allowed");
		}
		else if(!@move_uploaded_file($userfile['tmp_name'], $upload_file))
		{
			$encodeExplorer->setErrorString("failed_move");
		}
		else
		{
			chmod($upload_file, EncodeExplorer::getConfig("upload_file_mode"));
			Logger::logCreation($location->getDir(true, false, false, 0).$name, false);
			Logger::emailNotification($location->getDir(true, false, false, 0).$name, true);
		}
	}

	private function reArrayFiles($file_post) {
		$file_ary = array();
		$file_count = count($file_post['name']);
		$file_keys = array_keys($file_post);
		for ($i=0; $i<$file_count; $i++) {
			foreach ($file_keys as $key) {
				$file_ary[$i][$key] = $file_post[$key][$i];
			}
		}

		return $file_ary;
	}
	public static function delete_dir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir")
						FileManager::delete_dir($dir."/".$object);
					else
						unlink($dir."/".$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	public static function delete_file($file){
		if(is_file($file)){
			unlink($file);
		}
	}

	//
	// The main function, checks if the user wants to perform any supported operations
	//
	function run($location)
	{
		if(isset($_POST['userdir']) && strlen($_POST['userdir']) > 0){
			if($location->uploadAllowed() && GateKeeper::isUserLoggedIn() && GateKeeper::isAccessAllowed() && GateKeeper::isNewdirAllowed()){
				$this->newFolder($location, $_POST['userdir']);
			}
		}

		if(isset($_FILES['userfile']['name']) && is_array($_FILES['userfile']['name']) ){
			if($location->uploadAllowed() && GateKeeper::isUserLoggedIn() && GateKeeper::isAccessAllowed() && GateKeeper::isUploadAllowed()){
				$userfiles = $this->reArrayFiles($_FILES['userfile']);
				foreach ($userfiles as $userfile) {
					if (!empty($userfile['name'])) {
						$this->uploadFile($location, $userfile);
					}
				}
			}
		}
		if(isset($_GET['del'])){
			if(GateKeeper::isUserLoggedIn() && GateKeeper::isAccessAllowed() && GateKeeper::isDeleteAllowed()){
				$split_path = Location::splitPath($_GET['del']);
				$path = "";
				for($i = 0; $i < count($split_path); $i++){
					$path .= $split_path[$i];
					if($i + 1 < count($split_path))
						$path .= "/";
				}
				if($path == "" || $path == "/" || $path == "\\" || $path == ".")
					return;

				if(is_dir($path))
					FileManager::delete_dir($path);
				else if(is_file($path))
					FileManager::delete_file($path);

				// Refresh so 'del' param is not in address anymore
				EncodeExplorer::refresh();
			}
		}
	}
}
