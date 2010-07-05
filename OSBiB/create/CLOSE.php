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
* CLOSE class
*
* Close tidily and print HTML.
*
*	$Header: /cvsroot/bibliophile/OSBib/create/CLOSE.php,v 1.4 2005/06/29 20:22:05 sirfragalot Exp $
*
*****/
class CLOSE
{
// Constructor
	function CLOSE($pString = FALSE, $helpLink = TRUE)
	{
		include_once("MESSAGES.php");
		$this->messages = new MESSAGES();
		print $this->header();
		print $this->openBody($helpLink);
		print $this->closeBody($pString);
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
<!-- Javascript required for preview links when creating/editing styles.
-->
<script type="text/javascript" src="common.js"></script>
</head>

END;
	}
/**
* Open HTML body and print title table
*/
	function openBody($helpLinkDisplay)
	{
		$helpLink = $this->messages->text("heading", "helpStyles");
$pString = <<< END1
<body onload="init()">
<table class="titleTable" border="0" cellspacing="0" cellpadding="0" align="center">
	<tr class="" align="left" valign="top">
		<td class="" align="left" valign="top">
<!-- Heading -->
<h2>OSBib-Create</h2>
		</td>
		
END1;
	if($helpLinkDisplay)
	{
$pString .= <<< END2
		<td class="" align="left" valign="top">
			<a class="link" href="index.php">
			Home
			</a>
		</td>
		<td class="" align="right" valign="top">
			<a class="link" href="index.php?action=help" target="_blank">
			$helpLink
			</a>
		</td>
		
END2;
	}

$pString .= <<< END3
		<td class="" align="right" valign="top">
			<a class="imgLink" href="http://bibliophile.sourceforge.net/" target="_blank">
			<img src="bibliophile.gif" border="0" width="127" height="75" alt="BIBLIOPHILE" />
			</a>
		</td>
	</tr>
</table>

END3;

		return $pString;
	}
/**
* Close HTML body and print result
*/
	function closeBody($pString)
	{
return <<< END
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
