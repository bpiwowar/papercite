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
*	Miscellaneous HTML FORM processing
*
*	@author Mark Grimshaw
*
*	$Header: /cvsroot/bibliophile/OSBib/create/FORMMISC.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
*/
class FORMMISC
{
// constructor
	function FORMMISC()
	{
	}
// reduce the size of long text (in select boxes usually) to keep web browser display tidy
// optional $override allows the programmer to override the user set preferences
	function reduceLongText($text, $override = FALSE)
	{
		$limit = $override ? $override : 40;
		if(($limit != -1) && ($count = preg_match_all("/./", $text, $throwAway)) > $limit)
		{
			$start = floor(($limit/2) - 2);
			$length = $count - (2 * $start);
			$text = substr_replace($text, " ... ", $start, $length);
		}
		return $text;
	}
}
?>
