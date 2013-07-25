<?php

/**
 * Class for working with BibTex data. 
 * 
 * Most the code comes from OSBib 3.0
 * http://bibliophile.sourceforge.net under the GPL licence.
 *
 * @author B. Piwowarski
 * @date November 2011
 */

require_once("UTF8.php");
require_once("creators.php");

/**
 * A page range
 */
class BibtexPages {
  function BibtexPages($start, $end) {
    $this->start = (int)$start;
    $this->end = (int)$end;
  }
  function count() {
    return ($this->start ? 1 : 0) + ($this->end ? 1 : 0);
  }
}


/**
 * A set of bibtex entries
 */
class BibTexEntries {
  /**
   * This contains all the BibTeX entries
   */
  var $data = array();

  var $preamble = array();
  var $strings = array();

  function parse(&$s) {
    $this->preamble = $this->strings = $this->data = $this->undefinedStrings = array();
    $this->count = 0;
    $this->fieldExtract = TRUE;
    $this->removeDelimit = TRUE;
    $this->expandMacro = TRUE;
    $this->parseFile = TRUE;

    $this->loadBibtexString($s);
    $this->extractEntries();
    $this->returnArrays();
    foreach($this->data as &$entry) 
      BibTexEntries::_postProcessing($entry);
    
    return true;
  }

  function loadBibtexString(&$bibtex_string)
  {
    if(is_string($bibtex_string)){
      $this->bibtexString = explode("\n",$bibtex_string);    
    }
    else{
      $this->bibtexString = $bibtex_string;   
    }
    $this->parseFile = FALSE;
    $this->currentLine = 0;
  }

// Get a non-empty line from the bib file or from the bibtexString
	function getLine()
	{
		if($this->parseFile)
		{
			if(!feof($this->fid))
			{
				do
				{
					$line = trim(fgets($this->fid));
				}
				while(!feof($this->fid) && !$line);
				return $line;
			}
			return FALSE;
		}
		else
		{
            $line = null;
			while($this->currentLine < count($this->bibtexString))
			{
				$line = trim($this->bibtexString[$this->currentLine]);
				$this->currentLine++;
                if ($line) break;
			}
			return $line;
		}
	}
// Extract value part of @string field enclosed by double-quotes or braces.
// The string may be expanded with previously-defined strings
	function extractStringValue($string) 
	{
		// $string contains a end delimiter, remove it
		$string = trim(substr($string,0,strlen($string)-1));
		// remove delimiters and expand
		$string = $this->removeDelimitersAndExpand($string);
		return $string;
	}
// Extract a field
	function fieldSplit($seg)
	{
// echo "**** ";print_r($seg);echo "<BR>";
		// handle fields like another-field = {}
		$array = preg_split("/,\s*([-_.:,a-zA-Z0-9]+)\s*={1}\s*/U", $seg, PREG_SPLIT_DELIM_CAPTURE);
// echo "**** ";print_r($array);echo "<BR>";
		//$array = preg_split("/,\s*(\w+)\s*={1}\s*/U", $seg, PREG_SPLIT_DELIM_CAPTURE);
		if(!array_key_exists(1, $array))
			return array($array[0], FALSE);
		return array($array[0], $array[1]);
	}
// Extract and format fields
	function reduceFields($oldString)
	{
		// 03/05/2005 G. Gardey. Do not remove all occurences, juste one
		// * correctly parse an entry ended by: somefield = {aValue}}
		$lg = strlen($oldString);
		if($oldString[$lg-1] == "}" || $oldString[$lg-1] == ")" || $oldString[$lg-1] == ",")
			$oldString = substr($oldString,0,$lg-1);
		// $oldString = rtrim($oldString, "}),");
		$split = preg_split("/=/", $oldString, 2);
		$string = $split[1];
		while($string)
		{
			list($entry, $string) = $this->fieldSplit($string);
			$values[] = $entry;
		}
		foreach($values as $value)
		{
			$pos = strpos($oldString, $value);
			$oldString = substr_replace($oldString, '', $pos, strlen($value));
		}
		$rev = strrev(trim($oldString));
		if($rev{0} != ',')
			$oldString .= ',';
		$keys = preg_split("/=,/", $oldString);
		// 22/08/2004 - Mark Grimshaw
		// I have absolutely no idea why this array_pop is required but it is.  Seems to always be 
		// an empty key at the end after the split which causes problems if not removed.
		array_pop($keys);
		foreach($keys as $key)
		{
			$value = trim(array_shift($values));
			$rev = strrev($value);
			// remove any dangling ',' left on final field of entry
			if($rev{0} == ',')
				$value = rtrim($value, ",");
			if(!$value)
				continue;
			// 21/08/2004 G.Gardey -> expand macro
			// Don't remove delimiters now needs to know if the value is a string macro
			// $this->data[$this->count][strtolower(trim($key))] = trim($this->removeDelimiters(trim($value)));
			$key = UTF8::utf8_strtolower(trim($key));
			$value = trim($value);
			$this->data[$this->count][$key] = $value;
		}
// echo "**** ";print_r($this->data[$this->count]);echo "<BR>";
	}
// Start splitting a bibtex entry into component fields.
// Store the entry type and citation.
	function fullSplit($entry)
	{        
		$matches = preg_split("/@(.*)[{(](.*),/U", $entry, 2, PREG_SPLIT_DELIM_CAPTURE); 
		$this->data[$this->count]['entrytype'] = strtolower(trim($matches[1]));
		// sometimes a bibtex entry will have no citation key
		if(preg_match("/=/", $matches[2])) // this is a field
			$matches = preg_split("/@(.*)\s*[{(](.*)/U", $entry, 2, PREG_SPLIT_DELIM_CAPTURE);
		// print_r($matches); print "<P>";
		$this->data[$this->count]['cite'] = $matches[2];
		$this->data[$this->count]['bibtex'] = $entry;
		$this->reduceFields($matches[3]);
	}

// Grab a complete bibtex entry
	function parseEntry($entry)
	{
		$count = 0;
		$lastLine = FALSE;
		if(preg_match("/@(.*)([{(])/U", preg_quote($entry), $matches)) 
		{
			if(!array_key_exists(1, $matches))
				return $lastLine;
			if(preg_match("/string/i", trim($matches[1])))
				$this->strings[] = $entry;
			else if(preg_match("/preamble/i", trim($matches[1])))
				$this->preamble[] = $entry;
			else if(preg_match("/comment/i", $matches[1])); // MG (31/Jan/2006) -- ignore @comment
			else
			{
				if($this->fieldExtract)
					$this->fullSplit($entry);
				else
					$this->data[$this->count] = $entry;
				$this->count++;
			}
			return $lastLine;
		}
	}

// Remove delimiters from a string
	function removeDelimiters($string)
	{
		if($string  && ($string{0} == "\""))
		{
			$string = substr($string, 1);
			$string = substr($string, 0, -1);
		}
		else if($string && ($string{0} == "{"))
		{
			if(strlen($string) > 0 && $string[strlen($string)-1] == "}")
			{
				$string = substr($string, 1);
				$string = substr($string, 0, -1);
			}
		}
		else if(!is_numeric($string) && !array_key_exists($string, $this->strings)
			 && (array_search($string, $this->undefinedStrings) === FALSE))
		{
			$this->undefinedStrings[] = $string; // Undefined string that is not a year etc.
			return '';
		}
		return $string;
	}

// This function works like explode('#',$val) but has to take into account whether
// the character # is part of a string (i.e., is enclosed into "..." or {...} ) 
// or defines a string concatenation as in @string{ "x # x" # ss # {xx{x}x} }
	function explodeString($val)
	{
		$openquote = $bracelevel = $i = $j = 0; 
		while ($i < strlen($val))
		{
			if ($val[$i] == '"')
				$openquote = !$openquote;
			elseif ($val[$i] == '{')
				$bracelevel++;
			elseif ($val[$i] == '}')
				$bracelevel--;
			elseif ( $val[$i] == '#' && !$openquote && !$bracelevel )
			{
				$strings[] = substr($val,$j,$i-$j);
				$j=$i+1;
			}
			$i++;
		}
		$strings[] = substr($val,$j);
		return $strings;
	}

// This function receives a string and a closing delimiter '}' or ')' 
// and looks for the position of the closing delimiter taking into
// account the following Bibtex rules:
//  * Inside the braces, there can arbitrarily nested pairs of braces,
//    but braces must also be balanced inside quotes! 
//  * Inside quotes, to place the " character it is not sufficient 
//    to simply escape with \": Quotes must be placed inside braces. 
	function closingDelimiter($val,$delimitEnd)
	{
//  echo "####>$delimitEnd $val<BR>";
		$openquote = $bracelevel = $i = $j = 0; 
		while ($i < strlen($val))
		{
			// a '"' found at brace level 0 defines a value such as "ss{\"o}ss"
			if ($val[$i] == '"' && !$bracelevel)
				$openquote = !$openquote;
			elseif ($val[$i] == '{')
				$bracelevel++;
			elseif ($val[$i] == '}')
				$bracelevel--;
			if ( $val[$i] == $delimitEnd && !$openquote && !$bracelevel )
				return $i;
			$i++;
		}
// echo "--> $bracelevel, $openquote";
		return 0;
	}

// Remove enclosures around entry field values.  Additionally, expand macros if flag set.
	function removeDelimitersAndExpand($string, $inpreamble = FALSE)
	{
		// only expand the macro if flag set, if strings defined and not in preamble
		if(!$this->expandMacro || empty($this->strings) || $inpreamble)
			$string = $this->removeDelimiters($string);
		else
		{
			$stringlist = $this->explodeString($string);
			$string = "";
			foreach ($stringlist as $str)
			{
				// trim the string since usually # is enclosed by spaces
				$str = trim($str); 
				// replace the string if macro is already defined
				// strtolower is used since macros are case insensitive
				if (isset($this->strings[strtolower($str)]))
					$string .= $this->strings[strtolower($str)];
				else 
					$string .= $this->removeDelimiters(trim($str));
			}
		}
		return $string;
	}

// This function extract entries taking into account how comments are defined in BibTeX.
// BibTeX splits the file in two areas: inside an entry and outside an entry, the delimitation 
// being indicated by the presence of a @ sign. When this character is met, BibTex expects to 
// find an entry. Before that sign, and after an entry, everything is considered a comment! 
	function extractEntries()
	{
		$inside = $possibleEntryStart = FALSE;
		$entry="";
		while($line=$this->getLine())
		{
			if($possibleEntryStart)
				$line = $possibleEntryStart . $line;
			if (!$inside && strchr($line,"@"))
			{
				// throw all characters before the '@'
				$line=strstr($line,'@');
				if(!strchr($line, "{") && !strchr($line, "("))
					$possibleEntryStart = $line;
				elseif(preg_match("/@.*([{(])/U", preg_quote($line), $matches))
				{
					$inside = TRUE;
					if ($matches[1] == '{')
						$delimitEnd = '}';
					else
						$delimitEnd = ')';
					$possibleEntryStart = FALSE;
				}
			}
			if ($inside)
			{
			  $entry .= ($entry ?  "\n" : "") . $line;
				if ($j=$this->closingDelimiter($entry,$delimitEnd))
				{
					// all characters after the delimiter are thrown but the remaining 
					// characters must be kept since they may start the next entry !!!
					$lastLine = substr($entry,$j+1);
					$entry = substr($entry,0,$j+1);
					// Strip excess whitespaces from the entry 
					$entry = preg_replace('/\s\s+/', ' ', $entry);
					$this->parseEntry($entry);
					$entry = strchr($lastLine,"@");
					if ($entry) 
						$inside = TRUE;
					else 
						$inside = FALSE;
				}
			}
		}
	}

// Return arrays of entries etc. to the calling process.
	function returnArrays()
	{
		foreach($this->preamble as $value)
		{
			preg_match("/.*?[{(](.*)/", $value, $matches);
			$preamble = substr($matches[1], 0, -1);
			$preambles['bibtexPreamble'] = trim($this->removeDelimitersAndExpand(trim($preamble), TRUE));
		}
		if(isset($preambles))
			$this->preamble = $preambles;
		if($this->fieldExtract)
		{
			// Next lines must take into account strings defined by previously-defined strings
			$strings = $this->strings; 
			// $this->strings is initialized with strings provided by user if they exists
			// it is supposed that there are no substitutions to be made in the user strings, i.e., no # 
			$this->strings = isset($this->userStrings) ? $this->userStrings : array() ; 
			foreach($strings as $value) 
			{
				// changed 21/08/2004 G. Gardey
				// 23/08/2004 Mark G. account for comments on same line as @string - count delimiters in string value
				$value = trim($value);
				$matches = preg_split("/@\s*string\s*([{(])/i", $value, 2, PREG_SPLIT_DELIM_CAPTURE);
				$delimit = $matches[1];
				$matches = preg_split("/=/", $matches[2], 2, PREG_SPLIT_DELIM_CAPTURE);
				// macros are case insensitive
				$this->strings[strtolower(trim($matches[0]))] = $this->extractStringValue($matches[1]); 
			}
		}
		// changed 21/08/2004 G. Gardey
		// 22/08/2004 Mark Grimshaw - stopped useless looping.
		// removeDelimit and expandMacro have NO effect if !$this->fieldExtract
		if($this->removeDelimit || $this->expandMacro && $this->fieldExtract)
		{
			for($i = 0; $i < count($this->data); $i++)
			{
				foreach($this->data[$i] as $key => $value)
				// 02/05/2005 G. Gardey don't expand macro for bibtexCitation 
				// and bibtexEntryType
				if($key != 'cite' && $key != "bibtex" && $key != 'entrytype')
					$this->data[$i][$key] = trim($this->removeDelimitersAndExpand($this->data[$i][$key])); 
			}
		}
// EZ: Remove this to be able to use the same instance for parsing several files, 
// e.g., parsing a entry file with its associated abbreviation file
//		if(empty($this->preamble))
//			$this->preamble = FALSE;
//		if(empty($this->strings))
//			$this->strings = FALSE;
//		if(empty($this->data))
//			$this->data = FALSE;
		return array($this->preamble, $this->strings, $this->data, $this->undefinedStrings);
	}

  static function process_accents(&$text) {
    // Replace anything of the form (x, y are any character)
    // {\x{y}}
    // {\x{\i}}
    // {\xy}
    // \xy
    // \x{y}
    // \x{\i}
    $slash = '\\\\';
    $text = preg_replace_callback("#\{$slash(.)\{(.)\}\}#", "BibTexEntries::_accents_cb", $text);
    $text = preg_replace_callback("#\{$slash(.)\{$slash(i)\}\}#", "BibTexEntries::_accents_cb", $text);
    $text = preg_replace_callback("#\{$slash(.)(.)\}#", "BibTexEntries::_accents_cb", $text);
    $text = preg_replace_callback("#$slash(.)\{(.)\}#", "BibTexEntries::_accents_cb", $text);
    $text = preg_replace_callback("#$slash(.)\{$slash(i)\}#", "BibTexEntries::_accents_cb", $text);
    // When there are no braces, we require a non alphanumeric character
    $text = preg_replace_callback("#$slash([^a-zA-Z])(.)#", "BibTexEntries::_accents_cb", $text);
    $text = preg_replace_callback("#$slash([a-zA-Z])(.)(?![a-zA-Z])#", "BibTexEntries::_accents_cb", $text);
    //    $text = preg_replace_callback("#\\\\(?:['\"^`H~\.]|¨)\w|\\\\([LlcCoO]|ss|aa|AA|[ao]e|[OA]E|&)#", "BibTexEntries::_accents_cb", $text);
  }

  static $accents = array(
			  "'" => array("a" => "á", "e" => "é", "i" => "í", "o" => "ó", "u" => "ú", "z" => "ź", "c" => "ć",
                       "A" => "Á", "E" => "É", "I" => "Í", "O" => "Ó", "U" => "Ú", "Z" => "Ź"),
			  "`" => array("a" => "à", "e" => "è", "i" => "ì", "o" => "ò", "u" => "ù",
				       "A" => "À", "E" => "È", "I" => "Ì", "O" => "Ò", "U" => "Ù"),
			  '"' => array("a" => "ä", "e" => "ë", "i" => "ï", "o" => "ö", "u" => "ü",
				       "A" => "Ä", "E" => "Ë", "I" => "Ï", "O" => "Ö", "U" => "Ü"),
			  '^' => array("a" => "â", "e" => "ê", "i" => "î", "o" => "ô", "u" => "û", 
				       "A" => "Â", "E" => "Ê", "I" => "Î", "O" => "Ô", "U" => "Û"),
			  '.' => array("z" => "ż", 
				       "Z" => "Ż"),
			  '~' => array("a" => "ã", "n" => "ñ", "o" => "õ",
				           "A" => "Ã", "N" => "Ñ", "O" => "Õ"),
			  "a" => array("a" => "å", "e" => "æ",
				       "A" => "Å", "E" => "Æ"),
			  'c' => 'ç',
			  'C' => 'Ç',
			  'o' => array("" => "ø", "e" => "œ"),
			  'O' => ARRAY("" => "Ø", "E" => "Œ"),
			  's' => array("s" => "ß"),
			  'H' => array("o" => "ő", 
				       "O" => "Ő"),
			  'l' => "ł",
			  'L' => "Ł",
			  '&' => '&',
			  '_' => '_'
			  );
  

  static function _accents_cb($input) {
    
    if (!array_key_exists($input[1], BibTexEntries::$accents)) {
      return "$input[0]";
    }
    $a = &BibTexEntries::$accents[$input[1]];
    if (!is_array($a)) 
      return "$a$input[2]";

    if (!array_key_exists($input[2], $a)) {
      if (array_key_exists("", $a)) 
	return $a[""] . $input[2];
      return "$input[0]";
    }

    return  $a[$input[2]];
  }


  /** Format the title by preserving capitalisation */
  static function formatTitle($pString, $delimitLeft = '{', $delimitRight = '}')
  {
    $in_maths = false;
    $brace_level = 0;

    $newString = "";
    //print "<div style='font-weight:bold'>" . htmlentities($pString) . "</div>";

    $start = true;

    foreach(preg_split("/[\${}]/", 
		       $pString, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE) as $v) {

      // delimiter
      $c = $v[1] > 0 ? $pString[$v[1] - 1] : "";
      
      // Add the current string unless it is a brace and we are not in math mode
      if (($in_maths &&  ($c == '}' || $c == '{')) || $c == '$') 
	$newString .= $c;
      
      switch($c) {
      case '$':
	$in_maths = !$in_maths;
	break;
      case '{':
	$brace_level++;
	break;
      case '}':
	$brace_level--;
	break;
      default:
	break;
      }

      //print "<div>$brace_level [" . $pString[$v[1]-1] .": $v[1]] " . htmlentities($v[0]) . "</div>";

      if ($in_maths || $brace_level > 0)
	$newString .= $v[0];
      else
	$newString .= $start ? UTF8::utf8_ucfirst( UTF8::utf8_strtolower($v[0]) ) : UTF8::utf8_strtolower($v[0]);

      $start = false;



      // Error: return the original string
      if ($brace_level < 0) {
	print "Error while parsing";
	return $pString;
      }
    }

    return $newString;
  }

  /**
   * Extracting the data of one entry
   *
   * @access private
   * @param string $entry The entry
   * @return array The representation of the entry or false if there is a problem
   */
  static function _postProcessing(&$ret) {
    // Process accents
    foreach($ret as $key => &$value)
      if ($key != "entrytype" && $key != "bibtex" && $key != "cite")
	BibTexEntries::process_accents($value);

    // Remove braces and handles capitalization
    foreach(array("title","booktitle") as $f)
      if (in_array($f, array_keys($ret))) 
	$ret[$f] = BibTexEntries::formatTitle($ret[$f]);
    
    
    // Handling pages
    if (in_array('pages', array_keys($ret))) {
      $matches = array();
      if (preg_match("/^\s*(\d+)(?:\s*--?\s*(\d+))?\s*$/", $ret['pages'], $matches)) {
	$ret['pages'] = new BibtexPages($matches[1], sizeof($matches) > 2 ? $matches[2] : "");
      }
    }

    //Handling the authors
    if (in_array('author', array_keys($ret))) {
      $ret['author'] = BibTexEntries::_extractAuthors($ret['author']);
    }

    //Handling the editors
    if (in_array('editor', array_keys($ret))) {
      $ret['editor'] = BibTexEntries::_extractAuthors($ret['editor']);
    }

  }

  /**
   * Extracting the authors
   *
   * @access private
   * @param string $entry The entry with the authors
   * @return array the extracted authors
   */
  static function _extractAuthors($authors) {
      return BibtexCreators::parse($authors);
  }

} // end class BibTexEntries
   

?>
