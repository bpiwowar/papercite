<?php
/********************************
OSBib:
A collection of PHP classes to create and manage bibliographic formatting for OS bibliography software 
using the OSBib standard.

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net 
so that your improvements can be added to the release package.

Mark Grimshaw 2005
http://bibliophile.sourceforge.net
********************************/

/** Description of class EXPORT
* Format a bibliographic resource for output.
* 
* @author	Andrea Rossato
* @version	1
*/
class EXPORTFILTER
{
/**
* $dir is the path to STYLEMAP.php etc.
*/
	function EXPORTFILTER(&$ref, $output)
	{
	  $this->bibformat =& $ref;
	  $this->format = $output;
	}
/**
* Format for HTML or RTF/plain?
*
* @author	Mark Grimshaw
* @version	1
*
* @param	$data	Input string
* @param	$patterns	Optional preg pattern (usually used to highlight a search phrase)
* @param	$class		Option CSS class for highlighting a search phrase
*/
	function format($data)
        {
		if($this->format == 'html')
		{
/**
* Scan for search patterns and highlight accordingly
*/
/**
* Temporarily replace any URL - works for just one URL in the output string.
*/
			if(preg_match("/(<a.*>.*<\/a>)/i", $data, $match))
			{
				$url = preg_quote($match[1], '/');
				$data = preg_replace("/$url/", "OSBIB__URL__OSBIB", $data);
			}
			else
				$url = FALSE;
			$data = str_replace("\"", "&quot;", $data);
			$data = str_replace("<", "&lt;", $data);
			$data = str_replace(">", "&gt;", $data);    
			$data = preg_replace("/&(?![a-zA-Z0-9#]+?;)/", "&amp;", $data);
			$data = $this->bibformat->patterns ? 
				preg_replace($this->bibformat->patterns, 
				"<span class=\"" . $this->bibformat->patternHighlight . "\">$1</span>", $data) : $data;
			$data = preg_replace("/\[b\](.*?)\[\/b\]/is", "<strong>$1</strong>", $data);
        		$data = preg_replace("/\[i\](.*?)\[\/i\]/is", "<em>$1</em>", $data);
        		$data = preg_replace("/\[sup\](.*?)\[\/sup\]/is", "<sup>$1</sup>", $data);
        		$data = preg_replace("/\[u\](.*?)\[\/u\]/is", 
				"<span style=\"text-decoration: underline;\">$1</span>", $data);
// Recover any URL
			if($url)
				$data = str_replace("OSBIB__URL__OSBIB", $match[1], $data);
		}
		else if($this->format == 'rtf')
		{
			$data = preg_replace("/&#(.*?);/", "\\u$1", $data);
			$data = preg_replace("/\[b\](.*?)\[\/b\]/is", "{{\\b $1}}", $data);
        		$data = preg_replace("/\[i\](.*?)\[\/i\]/is", "{{\\i $1}}", $data);
        		$data = preg_replace("/\[u\](.*?)\[\/u\]/is", "{{\\ul $1}}", $data);
// Need to figure this one out for RTF
        		$data = preg_replace("/\[sup\](.*?)\[\/sup\]/is", "$1", $data);
		}
/**
* OpenOffice-1.x.
*/
		else if($this->format == 'sxw')
		{
			$data = $this->bibformat->utf8->decodeUtf8($data);
			$data = str_replace("\"", "&quot;", $data);
			$data = str_replace("<", "&lt;", $data);
			$data = str_replace(">", "&gt;", $data);    
			$data = preg_replace("/&(?![a-zA-Z0-9#]+?;)/", "&amp;", $data);
			$data = preg_replace("/\[b\](.*?)\[\/b\]/is", "<text:span text:style-name=\"textbf\">$1</text:span>", $data);
        		$data = preg_replace("/\[i\](.*?)\[\/i\]/is", "<text:span text:style-name=\"emph\">$1</text:span>", $data);
        		$data = preg_replace("/\[sup\](.*?)\[\/sup\]/is", "<text:span text:style-name=\"superscript\">$1</text:span>", $data);
        		$data = preg_replace("/\[u\](.*?)\[\/u\]/is", 
				"<text:span text:style-name=\"underline\">$1</text:span>", $data);
			$data = "<text:p text:style-name=\"Text body\">".$data."</text:p>\n";
		}
/**
* StripBBCode for plain.
*/
		else
			$data = preg_replace("/\[.*\]|\[\/.*\]/U", "", $data);
		return $data;
	}
}
?>