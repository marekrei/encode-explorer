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
// Initialising variables. Don't change these.
//

$_CONFIG = array();
$_ERROR = "";
$_START_TIME = microtime(TRUE);

/*
 * GENERAL SETTINGS
 */

//
// Choose a language. See below in the language section for options.
// Default: $_CONFIG['lang'] = "en";
//
$_CONFIG['lang'] = "en";

//
// Display thumbnails when hovering over image entries in the list.
// Common image types are supported (jpeg, png, gif).
// Pdf files are also supported but require ImageMagick to be installed.
// Default: $_CONFIG['thumbnails'] = true;
//
$_CONFIG['thumbnails'] = true;

//
// Maximum sizes of the thumbnails.
// Default: $_CONFIG['thumbnails_width'] = 200;
// Default: $_CONFIG['thumbnails_height'] = 200;
//
$_CONFIG['thumbnails_width'] = 300;
$_CONFIG['thumbnails_height'] = 300;

//
// Mobile interface enabled. true/false
// Default: $_CONFIG['mobile_enabled'] = true;
//
$_CONFIG['mobile_enabled'] = true;

//
// Mobile interface as the default setting. true/false
// Default: $_CONFIG['mobile_default'] = false;
//
$_CONFIG['mobile_default'] = false;

/*
 * USER INTERFACE
 */

//
// Will the files be opened in a new window? true/false
// Default: $_CONFIG['open_in_new_window'] = false;
//
$_CONFIG['open_in_new_window'] = false;

//
// How deep in subfolders will the script search for files?
// Set it larger than 0 to display the total used space.
// Default: $_CONFIG['calculate_space_level'] = 0;
//
$_CONFIG['calculate_space_level'] = 0;

//
// Will the page header be displayed? 0=no, 1=yes.
// Default: $_CONFIG['show_top'] = true;
//
$_CONFIG['show_top'] = true;

//
// The title for the page
// Default: $_CONFIG['main_title'] = "Encode Explorer";
//
$_CONFIG['main_title'] = "Encode Explorer";

//
// The secondary page titles, randomly selected and displayed under the main header.
// For example: $_CONFIG['secondary_titles'] = array("Secondary title", "&ldquo;Secondary title with quotes&rdquo;");
// Default: $_CONFIG['secondary_titles'] = array();
//
$_CONFIG['secondary_titles'] = array();

//
// Display breadcrumbs (relative path of the location).
// Default: $_CONFIG['show_path'] = true;
//
$_CONFIG['show_path'] = true;

//
// Display the time it took to load the page.
// Default: $_CONFIG['show_load_time'] = true;
//
$_CONFIG['show_load_time'] = true;

//
// The time format for the "last changed" column.
// Default: $_CONFIG['time_format'] = "d.m.y H:i:s";
//
$_CONFIG['time_format'] = "d.m.y H:i:s";

//
// Charset. Use the one that suits for you.
// Default: $_CONFIG['charset'] = "UTF-8";
//
$_CONFIG['charset'] = "UTF-8";

/*
* PERMISSIONS
*/

//
// The array of folder names that will be hidden from the list.
// Default: $_CONFIG['hidden_dirs'] = array("__encode");
//
$_CONFIG['hidden_dirs'] = array("__encode");

//
// Filenames that will be hidden from the list.
// Default: $_CONFIG['hidden_files'] = array(".ftpquota", "index.php", "index.php~", ".htaccess", ".htpasswd");
//
$_CONFIG['hidden_files'] = array(".ftpquota", "index.php", "index.php~", ".htaccess", ".htpasswd");

//
// Whether authentication is required to see the contents of the page.
// If set to false, the page is public.
// If set to true, you should specify some users as well (see below).
// Important: This only prevents people from seeing the list.
// They will still be able to access the files with a direct link.
// Default: $_CONFIG['require_login'] = false;
//
$_CONFIG['require_login'] = true;

//
// Usernames and passwords for restricting access to the page.
// The format is: array(username, password, status)
// Status can be either "user" or "admin". User can read the page, admin can upload and delete.
// For example: $_CONFIG['users'] = array(array("username1", "password1", "user"), array("username2", "password2", "admin"));
// You can also keep require_login=false and specify an admin.
// That way everyone can see the page but username and password are needed for uploading.
// For example: $_CONFIG['users'] = array(array("username", "password", "admin"));
// Default: $_CONFIG['users'] = array();
//
$_CONFIG['users'] = array();

//
// Permissions for uploading, creating new directories and deleting.
// They only apply to admin accounts, regular users can never perform these operations.
// Default:
// $_CONFIG['upload_enable'] = true;
// $_CONFIG['newdir_enable'] = true;
// $_CONFIG['delete_enable'] = false;
//
$_CONFIG['upload_enable'] = true;
$_CONFIG['newdir_enable'] = true;
$_CONFIG['delete_enable'] = false;

/*
 * UPLOADING
 */

//
// List of directories where users are allowed to upload.
// For example: $_CONFIG['upload_dirs'] = array("./myuploaddir1/", "./mydir/upload2/");
// The path should be relative to the main directory, start with "./" and end with "/".
// All the directories below the marked ones are automatically included as well.
// If the list is empty (default), all directories are open for uploads, given that the password has been set.
// Default: $_CONFIG['upload_dirs'] = array();
//
$_CONFIG['upload_dirs'] = array();

//
// MIME type that are allowed to be uploaded.
// For example, to only allow uploading of common image types, you could use:
// $_CONFIG['upload_allow_type'] = array("image/png", "image/gif", "image/jpeg");
// Default: $_CONFIG['upload_allow_type'] = array();
//
$_CONFIG['upload_allow_type'] = array();

//
// File extensions that are not allowed for uploading.
// For example: $_CONFIG['upload_reject_extension'] = array("php", "html", "htm");
// Default: $_CONFIG['upload_reject_extension'] = array();
//
$_CONFIG['upload_reject_extension'] = array("php", "php2", "php3", "php4", "php5", "phtml");

//
// By default, apply 0755 permissions to new directories
//
// The mode parameter consists of three octal number components specifying
// access restrictions for the owner, the user group in which the owner is
// in, and to everybody else in this order.
//
// See: https://php.net/manual/en/function.chmod.php
//
// Default: $_CONFIG['new_dir_mode'] = 0755;
//
$_CONFIG['new_dir_mode'] = 0755;

//
// By default, apply 0644 permissions to uploaded files
//
// The mode parameter consists of three octal number components specifying
// access restrictions for the owner, the user group in which the owner is
// in, and to everybody else in this order.
//
// See: https://php.net/manual/en/function.chmod.php
//
// Default: $_CONFIG['upload_file_mode'] = 0644;
//
$_CONFIG['upload_file_mode'] = 0644;

/*
 * LOGGING
 */

//
// Upload notification e-mail.
// If set, an e-mail will be sent every time someone uploads a file or creates a new dirctory.
// Default: $_CONFIG['upload_email'] = "";
//
$_CONFIG['upload_email'] = "";

//
// Logfile name. If set, a log line will be written there whenever a directory or file is accessed.
// For example: $_CONFIG['log_file'] = ".log.txt";
// Default: $_CONFIG['log_file'] = "";
//
$_CONFIG['log_file'] = "";

/*
 * SYSTEM
 */


//
// The starting directory. Normally no need to change this.
// Use only relative subdirectories!
// For example: $_CONFIG['starting_dir'] = "./mysubdir/";
// Default: $_CONFIG['starting_dir'] = ".";
//
$_CONFIG['starting_dir'] = ".";

//
// Location in the server. Usually this does not have to be set manually.
// Default: $_CONFIG['basedir'] = "";
//
$_CONFIG['basedir'] = "";

//
// Big files. If you have some very big files (>4GB), enable this for correct
// file size calculation.
// Default: $_CONFIG['large_files'] = false;
//
$_CONFIG['large_files'] = false;

//
// The session name, which is used as a cookie name.
// Change this to something original if you have multiple copies in the same space
// and wish to keep their authentication separate.
// The value can contain only letters and numbers. For example: MYSESSION1
// More info at: http://www.php.net/manual/en/function.session-name.php
// Default: $_CONFIG['session_name'] = "";
//
$_CONFIG['session_name'] = "";

//
// Defines where to find the JQuery file.
// Allowed values:
//  * "local": use the locally hosted file (at __encode/static/jquery.min.js)
//  * "cdn"  : use the CDN url provided by $_CONFIG['jquery_cdn_url']
//  * "none" : don't use JQuery (Warning: limited functionnality)
//
$_CONFIG['jquery_source'] = "cdn";

//
// URL to the JQuery javascript file (Usually a CDN).
// You can use the CDN of your choice or any custom URL. See:
// https://jquery.com/download/#using-jquery-with-a-cdn
// Respected only when $_CONFIG['jquery_source'] == "cdn".
// Default: $_CONFIG['session_name'] = "";
//
$_CONFIG['jquery_cdn_url'] = "https://code.jquery.com/jquery-3.5.1.min.js";

?>
