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
*	Success messages
*
*	@author Mark Grimshaw
*
*	$Header: /cvsroot/bibliophile/OSBib/create/SUCCESS.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
*/
class SUCCESS
{
// Constructor
	function SUCCESS()
	{
	}
/**
* Print the message
*/
	function text($indexName, $extra = FALSE)
	{
		include_once("MISC.php");
		include_once("../UTF8.php");
		$utf8 = new UTF8();
		$arrays = $this->loadArrays();
		$string = $arrays[$indexName];
		$string = $extra ?	preg_replace("/###/", $utf8->smartUtf8_decode($extra), $string) :
			preg_replace("/###/", "", $string);
		return MISC::p($utf8->encodeUtf8($string), "success", "center");
	}
// English success messages
	function loadArrays()
	{
		return array(
				"style"		=>	"Successfully###bibliographic style",
		);
	}
}
?>
