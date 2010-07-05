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
/*****
* CLOSEPOPUP class
*
* Close tidily and print HTML. this used for javascript pop-ups so does not include titles, GIFs etc.
*
*	$Header: /cvsroot/bibliophile/OSBib/create/CLOSEPOPUP.php,v 1.1 2005/06/25 02:57:34 sirfragalot Exp $
*
*****/
class CLOSEPOPUP
{
// Constructor
	function CLOSEPOPUP($pString = FALSE)
	{
		include_once("MESSAGES.php");
		$this->messages = new MESSAGES();
		print $this->header();
		print $this->printBody($pString);
		ob_end_flush();
		die;
	}
/**
* Print HTML header information
*/
	function header()
	{
return <<< END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
<title>OSBib-Create</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" href="osbib.css" type="text/css" />
</head>

END;
	}
/**
* Print result
*/
	function printBody($pString)
	{
return <<< END
<body>
<table class="mainTable" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="" align="left" valign="top">
<td class="" align="left" valign="top">
$pString
</td>
</tr>
</table>
</body>
</html>
END;
	}
}
?>
