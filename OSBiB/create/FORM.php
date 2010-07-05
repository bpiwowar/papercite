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
*	HTML FORM elements
*
*	@author Mark Grimshaw
*
*	$Header: /cvsroot/bibliophile/OSBib/create/FORM.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
*/
class FORM
{
// constructor
	function FORM()
	{
	}
// print form header with hidden action field
	function formHeader($action)
	{
		global $PHP_SELF;
		$pString = <<< END
<form method="post" action="$PHP_SELF">
<input type="hidden" name="action" value="$action" />
END;
		return $pString . "\n";
	}
// end a form
	function formEnd()
	{
		return "</form>\n";
	}
// print form header with hidden action field for multi-part upload forms
	function formMultiHeader($action)
	{
		global $PHP_SELF;
		$pString = <<< END
<form enctype="multipart/form-data" method="post" action="$PHP_SELF">
<input type="hidden" name="action" value="$action" />
END;
		return $pString . "\n";
	}
// print form footer with submit field
	function formSubmit($value = FALSE)
	{
		include_once("MESSAGES.php");
		$messages = new MESSAGES();
		if(!$value)
			$value = $messages->text("submit", "Submit");
		else
			$value = $messages->text("submit", $value);
		$pString = <<< END
<input type="submit" value=" $value " />
END;
		return $pString . "\n";
	}
// print form reset button
	function formReset()
	{
		include_once("MESSAGES.php");
		$messages = new MESSAGES();
		$value = $messages->text("submit", "reset");
		$pString = <<< END
<input type="reset" value=" $value " />
END;
		return $pString . "\n";
	}
// print hidden form input
	function hidden($name, $value)
	{
		$pString = <<< END
<input type="hidden" name="$name" value="$value" />
END;
		return $pString . "\n";
	}
// print radio button
	function radioButton($label, $name, $value = FALSE, $checked = FALSE)
	{
		$checked ? $checked = "checked=\"checked\"" : "";
		$pString = '';
		if($label)
			$pString = "$label:<br />";
		$pString .= <<< END
<input type="radio" name="$name" value="$value" $checked />
END;
		return $pString . "\n";
	}
// print checkbox
	function checkbox($label, $name, $checked = FALSE)
	{
		$checked ? $checked = "checked=\"checked\"" : "";
		$pString = '';
		if($label)
			$pString = "$label:<br />";
		$pString .= <<< END
<input type="checkbox" name="$name" $checked />
END;
		return $pString . "\n";
	}
// create select boxes for HTML forms
// requires $name, $array and optional $size.
// First OPTION is always SELECTED
// optional $override allows the programmer to override the user set preferences for character limiting in select boxes
	function selectFBox($label, $name, $array, $size = 3, $override = FALSE)
	{
		include_once("FORMMISC.php");
		$formMisc = new FORMMISC();
		$pString = '';
		if($label)
			$pString = "$label:<br />";
		$pString .= "<select name=\"$name\" id=\"$name\" size=\"$size\">\n";
		$value = array_shift($array);
		$string = $formMisc->reduceLongText($value, $override);
		$pString .= "<option value=\"$value\" selected=\"selected\">" . $string . "</option>\n";
		foreach($array as $value)
		{
			$string = $formMisc->reduceLongText($value, $override);
			$pString .= "<option value=\"$value\">$string</option>\n";
		}
		$pString .= "</select>\n";
		return $pString;
	}
// create select boxes for HTML forms
// requires $name, $array, selected value and optional $size.
// 'selected value' is set SELECTED
// optional $override allows the programmer to override the user set preferences for character limiting in select boxes
	function selectedBox($label, $name, $array, $select, $size = 3, $override = FALSE)
	{
		include_once("FORMMISC.php");
		$formMisc = new FORMMISC();
		$pString = '';
		if($label)
			$pString = "$label:<br />";
		$pString .= "<select name=\"$name\" id=\"$name\" size=\"$size\">\n";
		foreach($array as $value)
		{
			if($value == $select)
			{
				$string = $formMisc->reduceLongText($value, $override);
				$pString .= "<option value=\"$value\" selected=\"selected\">$string</option>\n";
			}
			else
			{
				$value = $formMisc->reduceLongText($value, $override);
				$pString .= "<option>$value</option>\n";
			}
		}
		$pString .= "</select>\n";
		return $pString;
	}
// create select boxes form HTML forms
// requires $name, $array and optional $size.
// First entry is default selection.
// OPTION VALUE is set so expects assoc. array where key holds this value
// optional $override allows the programmer to override the user set preferences for character limiting in select boxes
	function selectFBoxValue($label, $name, $array, $size = 3, $override = FALSE)
	{
		include_once("FORMMISC.php");
		$formMisc = new FORMMISC();
		$pString = '';
		if($label)
			$pString = "$label:<br />\n";
		$pString .= "<select name=\"$name\" id=\"$name\" size=\"$size\">\n";
		$pString .= "<option value=\"" . key($array) . "\" selected=\"selected\">" .
			$formMisc->reduceLongText(current($array), $override) . "</option>\n";
		$doneFirst = FALSE;
		foreach($array as $key => $value)
		{
			$value = $formMisc->reduceLongText($value, $override);
			if(!$doneFirst)
			{
				$doneFirst = TRUE;
				continue;
			}
			$pString .= "<option value=\"$key\">$value</option>\n";
		}
		$pString .= "</select>\n";
		return $pString;
	}
// create select boxes form HTML forms
// requires $name, $array and optional $size.
// $select is default selection.
// OPTION VALUE is set so expects assoc. array where key holds this value
// optional $override allows the programmer to override the user set preferences for character limiting in select boxes
	function selectedBoxValue($label, $name, $array, $select, $size = 3, $override = FALSE)
	{
		include_once("FORMMISC.php");
		$formMisc = new FORMMISC();
		$pString = '';
		if($label)
			$pString = "$label:<br />\n";
		$pString .= "<select name=\"$name\" id=\"$name\" size=\"$size\">\n";
		foreach($array as $key => $value)
		{
			$value = $formMisc->reduceLongText($value, $override);
			($key == $select) ?
				$pString .= "<option value=\"$key\" selected=\"selected\">$value</option>\n" :
				$pString .= "<option value=\"$key\">$value</option>\n";
		}
		$pString .= "</select>\n";
		return $pString;
	}
// create select boxes form HTML forms
// requires $name, $array and optional $size.
// First entry is default selection.
// OPTION VALUE is set so expects assoc. array where key holds this value.
// MULTIPLE values may be selected
// optional $override allows the programmer to override the user set preferences for character limiting in select boxes
	function selectFBoxValueMultiple($label, $name, $array, $size = 3, $override = FALSE)
	{
		include_once("FORMMISC.php");
		$formMisc = new FORMMISC();
		$pString = '';
		if($label)
			$pString = "$label:<br />\n";
		$name .= '[]';
		$pString .= "<select name=\"$name\" id=\"$name\" size=\"$size\" multiple=\"multiple\">\n";
		$pString .= "<option value=\"" . key($array) . "\" selected=\"selected\">" .
			$formMisc->reduceLongText(current($array), $override) . "</option>\n";
		$doneFirst = FALSE;
		foreach($array as $key => $value)
		{
			$value = $formMisc->reduceLongText($value, $override);
			if(!$doneFirst)
			{
				$doneFirst = TRUE;
				continue;
			}
			$pString .= "<option value=\"$key\">$value</option>\n";
		}
		$pString .= "</select>\n";
		return $pString;
	}
// create select boxes form HTML forms
// requires $name, $array, selected values (array of) and optional $size.
// OPTION VALUE is set so expects assoc. array where key holds this value.
// MULTIPLE values may be selected
// optional $override allows the programmer to override the user set preferences for character limiting in select boxes
	function selectedBoxValueMultiple($label, $name, $array, $values, $size = 3, $override = FALSE)
	{
		include_once("FORMMISC.php");
		$formMisc = new FORMMISC();
		include_once("MESSAGES.php");
		$messages = new MESSAGES();
		$pString = '';
		if($label)
			$pString = "$label:<br />\n";
		$name .= '[]';
		$pString .= "<select name=\"$name\" id=\"$name\" size=\"$size\" multiple=\"multiple\">\n";
		foreach($array as $key => $value)
		{
			if($value == $messages->text("misc", "ignore"))
			{
				$pString .= "<option value=\"$key\">$value</option>\n";
				continue;
			}
			$value = $formMisc->reduceLongText($value, $override);
			if(array_search($key, $values) !== FALSE)
				$pString .= "<option value=\"" . $key .
					"\" selected=\"selected\">" . $value . "</option>\n";
			else
				$pString .= "<option value=\"$key\">$value</option>\n";
		}
/* This is slow, slow, so slow! MG - 1/April/2005  Above is quicker (much, much quicker!)
		foreach($array as $key => $value)
		{
			$match = FALSE;
			$value = $formMisc->reduceLongText($value, $override);
			foreach($values AS $select)
			{
				if($value == $messages->text("misc", "ignore"))
					break;
				if($key == $select)
				{
					$pString .= "<option value=\"" . $key .
						"\" selected=\"selected\">" . $value . "</option>\n";
					$match = TRUE;
					break;
				}
			}
			if(!$match)
				$pString .= "<option value=\"$key\">$value</option>\n";
		}
*/
		$pString .= "</select>\n";
		return $pString;
	}
// password input type
	function passwordInput($label, $name, $value = FALSE, $size = 20, $maxLength = 255)
	{
		$pString = '';
		if($label)
			$pString = "$label:<br />";
		$pString .= <<< END
<input type="password" name="$name" value="$value" size="$size" maxlength="$maxLength" />
END;
		return $pString . "\n";
	}
// text input type
	function textInput($label, $name, $value = FALSE, $size = 20, $maxLength = 255)
	{
		$pString = '';
		if($label)
			$pString = "$label:<br />";
		$pString .= <<< END
<input type="text" name="$name" value="$value" size="$size" maxlength="$maxLength" />
END;
		return $pString . "\n";
	}
// textarea input type
	function textareaInput($label, $name, $value = FALSE, $cols = 30, $rows = 5)
	{
		$pString = '';
		if($label)
			$pString = "$label:<br />";
		$pString .= <<< END
<textarea name="$name" id="$name" cols="$cols" rows="$rows">$value</textarea>
END;
		return $pString . "\n";
	}
// upload box
	function fileUpload($label, $name, $size = 20)
	{
		$pString = '';
		if($label)
			$pString = "$label:<br />";
		$pString .= <<< END
<input type="file" name="$name" size="$size" />
END;
		return $pString . "\n";
	}
}
?>
