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
// File class holds the information about one file in the list
//


class File
{
	var $name;
	var $location;
	var $size;
	//var $extension;
	var $type;
	var $modTime;

	//
	// Constructor
	//
	function __construct($name, $location)
	{
		$this->name = $name;
		$this->location = $location;

		$this->type = File::getFileType($this->location->getDir(true, false, false, 0).$this->getName());
		$this->size = File::getFileSize($this->location->getDir(true, false, false, 0).$this->getName());
		$this->modTime = filemtime($this->location->getDir(true, false, false, 0).$this->getName());
	}

	function getName()
	{
		return $this->name;
	}

	function getNameEncoded()
	{
		return rawurlencode($this->name);
	}

	function getNameHtml()
	{
		return htmlspecialchars($this->name);
	}

	function getSize()
	{
		return $this->size;
	}

	function getType()
	{
		return $this->type;
	}

	function getModTime()
	{
		return $this->modTime;
	}

	//
	// Determine the size of a file
	//
	public static function getFileSize($file)
	{
		$sizeInBytes = filesize($file);

		// If filesize() fails (with larger files), try to get the size from unix command line.
		if (EncodeExplorer::getConfig("large_files") == true || !$sizeInBytes || $sizeInBytes < 0) {
			$sizeInBytes=exec("ls -l '$file' | awk '{print $5}'");
		}
		return $sizeInBytes;
	}

	public static function getFileType($filepath)
	{
		/*
		 * This extracts the information from the file contents.
		 * Unfortunately it doesn't properly detect the difference between text-based file types.
		 *
		$mime_type = File::getMimeType($filepath);
		$mime_type_chunks = explode("/", $mime_type, 2);
		$type = $mime_type_chunks[1];
		*/
		return File::getFileExtension($filepath);
	}

	public static function getFileMime($filepath)
	{
		$fhandle = finfo_open(FILEINFO_MIME);
		$mime_type = finfo_file($fhandle, $filepath);
		$mime_type_chunks = preg_split('/\s+/', $mime_type);
		$mime_type = $mime_type_chunks[0];
		$mime_type_chunks = explode(";", $mime_type);
		$mime_type = $mime_type_chunks[0];
		return $mime_type;
	}

	public static function getFileExtension($filepath)
	{
		return strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
	}

	//
	// Debugging output
	//
	function debug()
	{
		print("File name: ".$this->getName()."\n");
		print("File location: ".$this->location->getDir(true, false, false, 0)."\n");
		print("File size: ".$this->size."\n");
		print("File modTime: ".$this->modTime."\n");
	}

	function isImage()
	{
		$type = $this->getType();
		if($type == "png" || $type == "jpg" || $type == "gif" || $type == "jpeg")
			return true;
		return false;
	}

	function isPdf()
	{
		if(strtolower($this->getType()) == "pdf")
			return true;
		return false;
	}

	public static function isPdfFile($file)
	{
		if(File::getFileType($file) == "pdf")
			return true;
		return false;
	}

	function isValidForThumb()
	{
		if($this->isImage() || ($this->isPdf() && ImageServer::isEnabledPdf()))
			return true;
		return false;
	}
}

?>
