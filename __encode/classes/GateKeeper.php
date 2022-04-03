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
// The class controls logging in and authentication
//


class GateKeeper
{
	public static function init()
	{
		global $encodeExplorer;
		if(strlen(EncodeExplorer::getConfig("session_name")) > 0)
				session_name(EncodeExplorer::getConfig("session_name"));
		session_start();
		if(!empty($_SESSION['ee_user_name'])) {
			return true;
		}
		if (!isset($_SESSION['ee_user_name'])) {
			header('Location: https://ebdz.xyz/forum/');
			exit;
		}
		header('HTTP/1.0 403 Forbidden');
		die('You are not allowed to access explorer.');
		exit;
		if(isset($_GET['logout']))
		{
			$_SESSION['ee_user_name'] = null;
			$_SESSION['ee_user_pass'] = null;
		}

		if(isset($_POST['user_pass']) && strlen($_POST['user_pass']) > 0)
		{
			if(GateKeeper::isUser((isset($_POST['user_name'])?$_POST['user_name']:""), $_POST['user_pass']))
			{
				$_SESSION['ee_user_name'] = isset($_POST['user_name'])?$_POST['user_name']:"";
				$_SESSION['ee_user_pass'] = $_POST['user_pass'];

				// Refresh page
				EncodeExplorer::refresh();
			}
			else
				$encodeExplorer->setErrorString("wrong_pass");
		}
	}

	public static function isUser($userName, $userPass)
	{
		if(!empty($_SESSION['ee_user_name'])) {
			return true;
		}
		foreach(EncodeExplorer::getConfig("users") as $user)
		{
			if($user[0] == $userName && $user[1] == $userPass)
			{
				return true;
			}
		}
		return false;
	}

	public static function isLoginRequired()
	{
		if(EncodeExplorer::getConfig("require_login") == false){
			return false;
		}
		return true;
	}

	public static function isUserLoggedIn()
	{
		if (isset($_SESSION['ee_user_name'], $_SESSION['ee_user_right'], $_SESSION['ee_user_dir'])) {
			$userdir = $_SESSION['ee_user_dir'];
			$basepath = getcwd();
			if(!is_dir($basepath ."/" . $userdir)) {
				@mkdir($basepath ."/" . $userdir);
			}
			return true;
		}
		if(isset($_SESSION['ee_user_name'], $_SESSION['ee_user_pass']))
		{
			if(GateKeeper::isUser($_SESSION['ee_user_name'], $_SESSION['ee_user_pass']))
				return true;
		}
		return false;
	}

	public static function isAccessAllowed()
	{
		if(!GateKeeper::isLoginRequired() || GateKeeper::isUserLoggedIn())
			return true;
		return false;
	}

	public static function isUploadAllowed(){
		if(EncodeExplorer::getConfig("upload_enable") == true && GateKeeper::isUserLoggedIn() == true && GateKeeper::getUserStatus() == "admin")
			return true;
		return self::isUserDir();
	}

	public static function isNewdirAllowed(){
		if(EncodeExplorer::getConfig("newdir_enable") == true && GateKeeper::isUserLoggedIn() == true && GateKeeper::getUserStatus() == "admin")
			return true;
		return self::isUserDir();
	}

	public static function isDeleteAllowed(){
		if(EncodeExplorer::getConfig("delete_enable") == true && GateKeeper::isUserLoggedIn() == true && GateKeeper::getUserStatus() == "admin")
			return true;
		return self::isUserDir();
	}

	public static function isUserDir(){
		if(empty($_SESSION['ee_user_dir'])) {
			return false;
		}
		$userdir = $_SESSION['ee_user_dir'];
		$location = new Location();
		$location->init();
		$currentDir = $location->getDir(true, false, false, 0);
		return substr($currentDir, 0, strlen($userdir)) === $userdir;
	}

	public static function getUserStatus(){
		if (!empty($_SESSION['ee_user_right'])) {
			if($_SESSION['ee_user_right'] == "admin") {
				return "admin";
			}
			return $_SESSION['ee_user_right'];
		}
		return null;
		if(GateKeeper::isUserLoggedIn() == true && EncodeExplorer::getConfig("users") != null && is_array(EncodeExplorer::getConfig("users"))){
			foreach(EncodeExplorer::getConfig("users") as $user){
				if($user[0] != null && $user[0] == $_SESSION['ee_user_name'])
					return $user[2];
			}
		}
		return null;
	}

	public static function getUserName()
	{
		if(GateKeeper::isUserLoggedIn() == true && isset($_SESSION['ee_user_name']) && strlen($_SESSION['ee_user_name']) > 0)
			return $_SESSION['ee_user_name'];
		if(isset($_SERVER["REMOTE_USER"]) && strlen($_SERVER["REMOTE_USER"]) > 0)
			return $_SERVER["REMOTE_USER"];
		if(isset($_SERVER['PHP_AUTH_USER']) && strlen($_SERVER['PHP_AUTH_USER']) > 0)
			return $_SERVER['PHP_AUTH_USER'];
		return "an anonymous user";
	}

	public static function showLoginBox(){
		if(!GateKeeper::isUserLoggedIn() && count(EncodeExplorer::getConfig("users")) > 0)
			return true;
		return false;
	}
}

?>
