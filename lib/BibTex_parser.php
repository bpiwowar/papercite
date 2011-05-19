<?php

/**
 * Class for working with BibTex data
 * 
 * The parser ignores all the errors following the BibTeX syntax as defined in:
 * http://artis.imag.fr/~Xavier.Decoret/resources/xdkbibtex/bibtex_summary.html
 * 
 *
 * @author B. Piwowarski
 * @date May 2011
 */

/**
 * A list of creators (e.g., authors, editors)
 */
class BibtexCreators {
  function BibtexCreators(&$creators) {
    $this->creators = &$creators;
  }
  function count() {
    return sizeof($creators);
  }
}

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

  /**
   * Parses what is stored in content and clears the content if the parsing is successfull.
   *
   * @access public
   * @return boolean true on success and PEAR_Error if there was a problem
   */
  function parse(&$s)
  {
    $length = strlen($s);
    $pos = 0;
    $data = array();

    // Go to the next entry
    while (($pos = strpos($s, "@", $pos)) !== FALSE) {
      $startPos = $pos;

      // (1) Get bibtex type type and goes inside braces
      $pos++;
      $type = BibTexEntries::parseKey($s, $pos, $length);
      if (!$type) continue;
      $type = strtolower($type);
      BibTexEntries::skipWhitespace($s,$pos,$length);
      if ($s[$pos] != '{') {  continue; }
      $pos++;
      
      // --- A comment
      if ($type == "comment") {
	// Skip everything between balanced braces
	$open = 1;
	while ($pos < $length) {
	  if ($s[$pos] == '{') $open++;
	  if ($s[$pos] == '}')
	    if (--$open == 0) break;
	  $pos++;
	}
	// Search for the next entry
	continue;
      }

      // --- A string
      if ($type == "string") {
	$kv = &BibTexEntries::parseKeyValue();
	if ($kv) {
	}
	continue;
      } 

      // --- Another type of entry
      
      // Get the bibtex key
      BibTexEntries::skipWhitespace($s, $pos, $length);
      $key = BibTexEntries::parseKey($s, $pos, $length);
      if (!$key) continue; 
      $entry = array("cite" => $key, "entrytype" => $type);

      while (true) {
	BibTexEntries::skipWhitespace($s, $pos, $length);
	if ($s[$pos] != ',') 
	  if ($s[$pos] == '}') break;
	  else continue 2;
	$pos++;

	// Now, parse key = values entries
	$kv  = BibTexEntries::parseKeyValue($s, $pos, $length);
	// Skip if something's wrong
	if (!$kv) continue 2;
	
	$entry[$kv[0]] = $kv[1];
      }

      //      foreach($entry as $key => $value)
      // print "<div><b>$key</b>: $value</div>";

      $entry["bibtex"] = substr($s, $startPos, $pos - $startPos + 1);
      //      print htmlentities($entry["bibtex"]);
      //      print  "<div>----</div>";
      BibTexEntries::_postProcessing($entry);
      $this->data[] = $entry;
    }


    //    print_r($this->data);
    return true;
  }

  /**
   * Skip the whitespaces
   */
  static function skipWhitespace($s, &$pos, $length) {
    while ($pos < $length && ctype_space($s[$pos]))
      $pos++;
  }

  
  /**
   * Parses key = value
   */
  static function parseKeyValue($s, &$pos, $length) {
    // --- Get the key
    BibTexEntries::skipWhitespace($s,$pos,$length);
    $key = BibTexEntries::parseKey($s, $pos, $length);
    if (!$key) {  return false; }
    $key = strtolower($key);

    BibTexEntries::skipWhitespace($s,$pos,$length);
    if ($s[$pos] != '=') return false;
    $pos++;
    BibTexEntries::skipWhitespace($s,$pos,$length);

    $value = BibTexEntries::parseValue($s, $pos, $length);
    if ($value === false) return false;
    return Array($key, $value);
  }

  /**
   * Parses a value
   */
  static function parseValue($s, &$pos, $length) {
    $value = "";
    while (true) {
      $c = $s[$pos];
      if ($c == '"' || $c == '{') {
	// Read until matching brace
	$quotes = $c == '"';
	$n = $quotes ? 0 : 1;
	while ($pos < $length) {
	  $c = $s[++$pos];
	  // Closing
	  if ($c == '}') {
	    $n -= 1;
	    if ($n < 0) return false;
	    if ($n == 0 && !$quotes) break;
	  } else if ($c == '{') $n++;
	  else if ($n == 0 && $c == '"') break;
	  else $value .= $c;
	}
	$pos++;
      } else if (ctype_alpha($s[$pos])) {
	// Macro
	$value = BibTexEntries::parseKey($s, $pos, $length);
      }

      // Stop unless we have a concatenation with hash (#)
      BibTexEntries::skipWhitespace($s,$pos,$length);
      if ($s[$pos] != "#") break;
      $pos++;
      BibTexEntries::skipWhitespace($s,$pos,$length);
    }
    
    // Returns the parsed value
    return $value;
  }

 /**
   * Parse an identifier
   */
  static function parseKey($s, &$pos, $length) {
    $id = "";
    while ($pos < $length) {
      $c = $s[$pos];
      if (strpos('{}@, ', $c) !== FALSE) break;
      $id .= $c;
      $pos++;
    }
    return $id;
  }


  static function process_accents(&$text) {
    $text = preg_replace_callback("#\\\\(?:['\"^`H~\.]|¨)\w|\\\\([LlcCoO]|ss|aa|AA|[ao]e|[OA]E|&)#", "BibTexEntries::_accents_cb", $text);
  }

  static $accents = array(
			  "\'a" => "á", "\`a" => "à", "\^a" => "â", "\¨a" => "ä", '\"a' => "ä",
			  "\'A" => "Á", "\`A" => "À", "\^A" => "Â", "\¨A" => "Ä", '\"A' => "Ä",
			  "\aa" => "å", "\AA" => "Å", "\ae" => "æ", "\AE" => "Æ",
			  "\cc" => "ç",
			  "\cC" => "Ç",
			  "\'e" => "é", "\`e" => "è", "\^e" => "ê", "\¨e" => "ë", '\"e' => "ë",
			  "\'E" => "é", "\`E" => "È", "\^E" => "Ê", "\¨E" => "Ë", '\"E' => "Ë",
			  "\'i" => "í", "\`i" => "ì", "\^i" => "î", "\¨i" => "ï", '\"i' => "ï",
			  "\'I" => "Í", "\`I" => "Ì", "\^I" => "Î", "\¨I" => "Ï", '\"I' => "Ï",
			  "\l" => "ł", 
			  "\L" => "Ł",
			  "\~n" => "ñ",
			  "\~N" => "Ñ",
			  "\o" => "ø", "\oe" => "œ",
			  "\O" => "Ø", "\OE" => "Œ",
			  "\'o" => "ó", "\`o" => "ò", "\^o" => "ô", "\¨o" => "ö", '\"o' => "ö", "\~o" => "õ", "\Ho" => "ő",
			  "\'O" => "Ó", "\`o" => "Ò", "\^O" => "Ô", "\¨O" => "Ö", '\"O' => "Ö", "\~O" => "Õ", "\HO" => "Ő",
			  '\ss' => "ß",
			  "\'u" => "ú", "\`u" => "ù", "\^u" => "û", "\¨u" => "ü", '\"u' => "ü",
			  "\'U" => "Ú", "\`U" => "Ù", "\^U" => "Û", "\¨U" => "Ü", '\"U' => "Ü", 
			  "\'z" => "ź", "\.z" => "ż",
			  "\'Z" => "Ź", "\.Z" => "Ż",
			  "\&" => "&"
			  ); 

  static function _accents_cb($input) {
    if (!array_key_exists($input[0], BibTexEntries::$accents))
      return "$input[0]";
    return  BibTexEntries::$accents[$input[0]];
  }

  /**
   * Extracting the data of one content
   *
   * @access private
   * @param string $entry The entry
   * @return array The representation of the entry or false if there is a problem
   */
  static function _postProcessing(&$ret) {
    // Process accents
    foreach($ret as $key => &$value) 
      if ($key != "bibtex" && $key != "cite")
	BibTexEntries::process_accents($value);
    
    // Handling pages
    if (in_array('pages', array_keys($ret))) {
      $matches = array();
      if (preg_match("/^\s*(\d+)(?:\s*--?\s*(\d+))?\s*$/", $ret['pages'], $matches)) {
	$ret['pages'] = new BibtexPages($matches[1], $matches[2]);
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
    function _extractAuthors($authors) {
      // Use OSBib way of parsing authors
      require_once("PARSECREATORS.php");
      $parseCreators = new PARSECREATORS();
      $creators = $parseCreators->parse($authors);
      foreach($creators as &$cArray) {
	$cArray = array(
			"surname" => trim($cArray[2]),
			"firstname" => trim($cArray[0]),
			"initials" => trim($Array[1]),
			"prefix" => trim($cArray[3])
			);
	unset($cArray);
      }
      return new BibtexCreators($creators);
    }

} // end class BibTexEntries
   

?>
