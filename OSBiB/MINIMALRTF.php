<?php
/*
MINIMALRTF - A minimal set of RTF coding methods to produce Rich Text Format documents on the fly.
v1.1

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net so that your improvements can be added to the release package.

Mark Grimshaw 2004
http://bibliophile.sourceforge.net
*/

// COMMAND LINE TESTS:
// For a quick command-line test (php -f MINIMALRTF.php) after installation, uncomment the following:
/**************************************************
$centred = "This is some centred text.";
$full = "This is some full justified and italicized text.";
$weird = "Indented UNICODE:  ¿ßŽŒ‰ﬂ™ŁÞßØ€∑≠◊∝∞∅Ωπ¿";
$rtf = new MINIMALRTF();
$string = $rtf->openRtf();
$rtf->createFontBlock(0, "Arial");
$rtf->createFontBlock(1, "Times New Roman");
$string .= $rtf->setFontBlock();
$string .= $rtf->justify("centre");
$string .= $rtf->textBlock(0, 12, $centred);
$string .= $rtf->justify("full");
$string .= $rtf->paragraph();
$string .= $rtf->textBlock(1, 12, $rtf->italics($full));
$string .= $rtf->justify("full", 2, 2);
$string .= $rtf->paragraph();
// Depending on your character set, you may need to encode $weird as UTF-8 first using PHP's inbuilt utf8_encode() function:
// $weird = $rtf->utf8_2_unicode(utf8_encode($weird));
$weird = $rtf->utf8_2_unicode($weird);
$string .= $rtf->textBlock(1, 12, $weird);
$string .= $rtf->closeRtf();

// Copy and paste the commandline output to a text editor, save with a .rtf extension and load in a word processor.
print $string . "\n\n";

**************************************************/

class MINIMALRTF
{
	/**
	* Constructor method called by user.
	*/
	function MINIMALRTF()
	{
		/**
		 * some defaults
		 */
		$this->justify = array(
					"centre"	=>	"qc",
					"left"		=>	"qj",
					"right"		=>	"qr",
					"full"		=>	"qj",
				);
	}
	/**
	* Create the RTF opening tag
	* @return string
	*/
	function openRtf()
	{
		return "{\\rtf1\\ansi\\ansicpg1252\n\n";
	}
	/**
	* Create the RTF closing tag
	* @return string
	*/
	function closeRtf()
	{
		return "\n}\n\n";
	}
	/**
	* Convert input text to bold text
	* @parameter string $input - text to be converted
	*/
	function bold($input = "")
	{
		return "{\b $input }";
	}
	/**
	* Convert input text to italics text
	* @parameter string $input - text to be converted
	*/
	function italics($input = "")
	{
		return "{\i $input }";
	}
	/**
	* Convert input text to underline text
	* @parameter string $input - text to be converted
	*/
	function underline($input = "")
	{
		return "{\ul $input }";
	}
	/**
	* Set font size for each paragraph
	* @parameter integer $number - number of this fontblock
	* @parameter string $font - required font
	*/
	function createFontBlock($fontBlock = FALSE, $font = FALSE)
	{
		if(($fontBlock === FALSE) || ($font === FALSE))
			return FALSE;
		$this->fontBlocks[] = "{\\f$fontBlock\\fcharset0 $font;}\n";
		return TRUE;
	}
	/**
	* Set font blocks
	* @return string fontblock string
	*/
	function setFontBlock()
	{
		if(!isset($this->fontBlocks))
			return FALSE;
		$string = "{\\fonttbl\n";
		foreach($this->fontBlocks as $fontBlock)
			$string .= $fontBlock;
		$string .= "}\n\n";
		return $string;
	}
	/**
	* Justify and indent
	* Each TAB is equivalent to 720 units of indent
	* @parameter string $justify - either "centre", "left", "right" or "full"
	* @parameter integer $indentL - no. TABs to indent from the left
	* @parameter integer $indentR - no. TABs to indent from the right
	*/
	function justify($justify = "full", $indentL = 0, $indentR = 0)
	{
		if(!array_key_exists($justify, $this->justify))
			$justifyC = "qj";
		else
			$justifyC = $this->justify[$justify];
		$indentL *= 720;
		$indentR *= 720;
		return "\\$justifyC\\li$indentL\\ri$indentR\n";
	}
	/**
	* Create empty paragraph
	* Font Size is twice what is shown in a word processor
	* @return string 
	*/
	function paragraph($fontBlock = 0, $fontSize = 12)
	{
		$fontSize *= 2;
		return "{\\f$fontBlock\\fs$fontSize \\par }\n";
	}
	/**
	* Create text block
	* @parameter string $input - input string
	* @return string 
	*/
	function textBlock($fontBlock = FALSE, $fontSize = FALSE, $input = FALSE)
	{
		if(($fontBlock === FALSE) || ($fontSize === FALSE) || ($input === FALSE))
			return FALSE;
		$fontSize *= 2;
		return "{\\f$fontBlock\\fs$fontSize $input \\par }\n";
	}
        /**
         * UTF-8 to unicode
         * returns an array of unicode character codes 
         * Code adapted from opensource PHP code by Scott Reynen at:
         * http://www.randomchaos.com/document.php?source=php_and_unicode
         *
         * @parameter string $string UTF-8 encoded string
         * @return array unicode character code
         */
	function utf8_2_unicode($string)
	{
		$unicode = array();        
		$values = array();
		$lookingFor = 1;
		for($i = 0; $i < strlen($string); $i++)
		{
			$thisValue = ord($string[$i]);
			if($thisValue < 128)
				$unicode[] = $string[$i];
			else
			{
				if(count($values) == 0)
					$lookingFor = ($thisValue < 224) ? 2 : 3;
				$values[] = $thisValue;
				if(count($values) == $lookingFor)
				{
					$number = ($lookingFor == 3) ?
						(($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64) :
						(($values[0] % 32) * 64) + ($values[1] % 64);
					$unicode[] = '\u' . $number . " ?";
					$values = array();
					$lookingFor = 1;
				}
			}
		}
		return join('', $unicode);
	}
}
?>
