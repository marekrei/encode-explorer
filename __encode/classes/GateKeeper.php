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

		if(count(EncodeExplorer::getConfig("users")) > 0)
			session_start();
		else
			return;

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
			else
				$encodeExplorer->setErrorString("wrong_pass");
		}
	}

	public static function isUser($userName, $userPass)
	{
		foreach(EncodeExplorer::getConfig("users") as $user)
		{
			if($user[1] == $userPass)
			{
				if(strlen($userName) == 0 || $userName == $user[0])
				{
					return true;
				}
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
		return false;
	}

	public static function isNewdirAllowed(){
		if(EncodeExplorer::getConfig("newdir_enable") == true && GateKeeper::isUserLoggedIn() == true && GateKeeper::getUserStatus() == "admin")
			return true;
		return false;
	}

	public static function isDeleteAllowed(){
		if(EncodeExplorer::getConfig("delete_enable") == true && GateKeeper::isUserLoggedIn() == true && GateKeeper::getUserStatus() == "admin")
			return true;
		return false;
	}

	public static function getUserStatus(){
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
