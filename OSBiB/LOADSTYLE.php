<?php
/********************************
OSBib:
A collection of PHP classes to manage bibliographic formatting for OS bibliography software 
using the OSBib standard.  Taken from WIKINDX (http://wikindx.sourceforge.net).

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net 
so that your improvements can be added to the release package.

Mark Grimshaw 2005
http://bibliophile.sourceforge.net
********************************/
/*****
* LOADSTYLE class
*
*****/
class LOADSTYLE
{
	function LOADSTYLE()
	{
	}
/**
* Read $stylesDir directory for XML style files and return an associative array. Each XML file should
* be within its own folder within $stylesDir.  This folder name should match the first part of the XML file name e.g.
* apa/APA.xml or chicago/CHICAGO.xml
*
* @author	Mark Grimshaw
* @version	1
*
* @param	$stylesDir	Path to styles directory
* @return	Sorted assoc. array - keys = filename (less '.xml'), values = Style description.
*/
	function loadDir($stylesDir)
	{
		$handle = opendir($stylesDir);
		while(FALSE !== ($dir = readdir($handle)))
		{
			$fileName = strtoupper($dir) . ".xml";
			if(is_dir($stylesDir . '/' . $dir)
				&& file_exists($stylesDir . '/' . $dir . "/" . $fileName))
			{
				if($fh = fopen($stylesDir . '/' . $dir . "/" . $fileName, "r"))
				{
					preg_match("/<description>(.*)<\\/description>/i", fgets($fh), $matches);
					$array[strtoupper($dir)] = $matches[1];
				}
				fclose($fh);
			}
		}
		if(!isset($array))
			return $array = array();
/**
* Sort alphabetically on the key.
*/
		ksort($array);
		return $array;
	}
}
?>
