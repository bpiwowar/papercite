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
*	HTML TABLE elements
*
*	@author Mark Grimshaw
*
*	$Header: /cvsroot/bibliophile/OSBib/create/TABLE.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
*/
class TABLE
{
// constructor
	function TABLE()
	{
	}
// code for starting a table
	function tableStart($class = FALSE, $border = 0, $spacing = 0, $padding = 0, $align = "center", $width="100%")
	{
		$string = <<< END
<table class="$class" border="$border" cellspacing="$spacing" cellpadding="$padding" align="$align" width="$width">
END;
		return $string . "\n";
	}
// code for ending a table
	function tableEnd()
	{
		$string = <<< END
</table>
END;
		return $string . "\n";
	}
// return properly formatted <tr> start tag
	function trStart($class = FALSE, $align = "left", $vAlign = "top")
	{
		$string = <<< END
<tr class="$class" align="$align" valign="$vAlign">
END;
		return $string . "\n";
	}
// return properly formatted <tr> end tag
	function trEnd()
	{
		$string = <<< END
</tr>
END;
		return $string . "\n";
	}
// return properly formatted <td> tag
	function td($data, $class = FALSE, $align = "left", $vAlign = "top", $colSpan = FALSE, $width=FALSE)
	{
		$string = <<< END
<td class="$class" align="$align" valign="$vAlign" colspan="$colSpan" width="$width">
$data
</td>
END;
		return $string . "\n";
	}
// return start TD tag
	function tdStart($class = FALSE, $align = "left", $vAlign = "top", $colSpan = FALSE)
	{
		return "<td class=\"$class\" align=\"$align\" valign=\"$vAlign\" colspan=\"$colSpan\">\n";
	}
// return td end tag
	function tdEnd()
	{
		return "</td>\n";
	}
}
?>
