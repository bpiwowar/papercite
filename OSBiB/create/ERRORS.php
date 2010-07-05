<?php
/********************************
OSBib:
A collection of PHP classes to create and manage bibliographic formatting for OS bibliography software 
using the OSBib standard.

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net 
so that your improvements can be added to the release package.

Adapted from WIKINDX: http://wikindx.sourceforge.net

Mark Grimshaw 2005
http://bibliophile.sourceforge.net
********************************/
/**
*	Error messages
*
*	@author Mark Grimshaw
*
*	$Header: /cvsroot/bibliophile/OSBib/create/ERRORS.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
*/
class ERRORS
{
// Constructor
	function ERRORS()
	{
	}
/**
* Print the message
*/
	function text($arrayName, $indexName, $extra = FALSE)
	{
		include_once("MISC.php");
		include_once("../UTF8.php");
		$utf8 = new UTF8();
		$arrays = $this->loadArrays();
		$string = $arrays[$arrayName][$indexName];
		$string = $extra ?	preg_replace("/###/", $utf8->smartUtf8_decode($extra), $string) :
			preg_replace("/###/", "", $string);
		return MISC::p($utf8->encodeUtf8($string), "error", "center");
	}
// English errors
	function loadArrays()
	{
		return array(
			"sessionError" => array(
				"write"		=>	"Unable to write to session.",
			),
// General user input errors
			"inputError" => array(
				"nan"		=>	"Input is not a number.###",
				"missing"	=>	"Missing input.###",
				"invalid"	=>	"Invalid input.###",
				"styleExists"	=>	"That style already exists",
			),
// File operations (import/export)
			"file"	=> array(
				'write'			=>	"Unable to write to file###",
				'noSql'			=>	"You must first list or select resources",
				"read"			=>	"Unable to read directory or file",
				"empty"			=>	"You have not yet exported any files",
				"upload"		=>	"File upload error",
				"folder"		=>	"Unable to create directory",
			),
		);
	}
}
?>
