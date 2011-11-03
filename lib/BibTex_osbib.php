<?php

/**
 * Class for working with BibTex data. 
 * 
 * Most of it comes from OSBib
 * http://bibliophile.sourceforge.net under the GPL licence.
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

  var $preamble = array();
  var $strings = array();

  function parse(&$s) {
    $this->fieldExtract = TRUE;
    $this->removeDelimit = TRUE;
    $this->expandMacro = TRUE;
    $this->parseFile = FALSE;

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


  function getLine()
  {
    // 21/08/2004 G.Gardey
    // remove comments from parsing
    if($this->parseFile){
      if(!feof($this->fid)){
	do{
	  $line = trim(fgets($this->fid));
	  $isComment = (strlen($line) > 0) ? $line[0] == '%' : FALSE;
	}
	while(!feof($this->fid) && $isComment);
	return $line;
      }
      return FALSE;
    }
    else{
      do{
	$line = trim($this->bibtexString[$this->currentLine]);
	$isComment = (strlen($line)>0) ? $line[0] == '%' : FALSE;
	$this->currentLine++;
      }
      while($this->currentLine <count($this->bibtexString) && $isComment);
      $val = ($this->currentLine < count($this->bibtexString)) ? $line : FALSE;
      return $val;
    }
  }
  // Count entry delimiters
  function braceCount($line, $delimitStart)
  {   
    if($delimitStart == '{')
      $delimitEnd = '}';
    else
      {
	$delimitStart = '(';
	$delimitEnd = ')';
      }
    $count = 0;
    $count = substr_count($line, $delimitStart);
    $count -= substr_count($line, $delimitEnd);
    return $count;
  }

  // Extract value part of @string field enclosed by double-quotes.
  function extractStringValue($string)
  {
    // 2/05/2005 G. Gardey Add support for @string macro
    // defined by curly bracket : @string{M12 = {December}}
    $oldvalue = $this->expandMacro;
    $this->expandMacro = false;
    // $string contains a end delimiter
    // remove it
    $string = trim(substr($string,0,strlen($string)-1));
    // remove delimiters
    $string = $this->removeDelimiters($string);
    // restore expandMacro
    $this->expandMacro = $oldvalue;
    return $string;
  }
  // Extract a field
  function fieldSplit($seg)
  {
    // handle fields like another-field = {}
    $array = preg_split("/,\s*([-_.:,a-zA-Z0-9]+)\s*={1}\s*/U", $seg, PREG_SPLIT_DELIM_CAPTURE);
    //$array = preg_split("/,\s*(\w+)\s*={1}\s*/U", $seg, PREG_SPLIT_DELIM_CAPTURE);
    if(!array_key_exists(1, $array))
      return array($array[0], FALSE);
    return array($array[0], $array[1]);
  }
  // Extract and format fields
  function reduceFields($oldString)
  {
    // 03/05/2005 G. Gardey. Do not remove all occurences, juste one
    //              * correctly parse an entry ended by: somefield = {aValue}}
    $lg = strlen($oldString);
    if($oldString[$lg-1] == "}" || $oldString[$lg-1] == ")" || $oldString[$lg-1] == ","){
      $oldString = substr($oldString,0,$lg-1);
    }
    //		$oldString = rtrim($oldString, "}),");
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
    // I have absolutely no idea why this array_pop is required but it is.  Seems to always be an empty key at the end after the split 
    // which causes problems if not removed.
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
	// Don't remove delimiters now
	// needs to know if the value is a string macro
	//			$this->data[$this->count][strtolower(trim($key))] = trim($this->removeDelimiters(trim($value)));
	$key = strtolower(trim($key));
	$value = trim($value);
	$this->data[$this->count][$key] = $value;
      }
  }
  // Start splitting a bibtex entry into component fields.
  // Store the entry type and citation.
  function fullSplit($entry)
  {        
    $matches = preg_split("/@(.*)\s*[{(](.*),/U", $entry, 2, PREG_SPLIT_DELIM_CAPTURE);
    $this->data[$this->count]['entrytype'] = strtolower($matches[1]);
    // sometimes a bibtex file will have no citation key
    if(preg_match("/=/", $matches[2])) // this is a field
      $matches = preg_split("/@(.*)\s*[{(](.*)/U", $entry, 2, PREG_SPLIT_DELIM_CAPTURE);
    //print_r($matches); print "<P>";
    $this->data[$this->count]['cite'] = trim($matches[2]);
    $this->data[$this->count]['bibtex'] = $entry;
    $this->reduceFields($matches[3]);
  }

  // Grab a complete bibtex entry
  function getEntry($line)
  {
    $entry = '';
    $count = 0;
    $lastLine = FALSE;
    if(preg_match("/@(.*)\s*([{(])/", preg_quote($line), $matches))
      {
	do
	  {
	    $count += $this->braceCount($line, $matches[2]);
	    $entry .= "\n" . $line;
	    if(($line = $this->getLine()) === FALSE)
	      break;
	    $lastLine = $line;
	  }
	while($count);
            
      }
    else
      {
	$line .= $this->getLine();
	$this->getEntry($line);
      }
    if(!array_key_exists(1, $matches))
      return $lastLine;
    if(preg_match("/string/i", $matches[1]))
      $this->strings[] = $entry;
    else if(preg_match("/preamble/i", $matches[1]))
      $this->preamble[] = $entry;
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
	
  // 02/05/2005 G.Gardey	only remove delimiters from a string
  function removeDelimiters($string){
    // MG 10/06/2005 - Make a note of whether delimiters exist - required in removeDelimitersAndExpand() otherwise, expansion happens everywhere including 
    // inside {...} and "..."
    $this->delimitersExist = FALSE;
    if($string  && ($string{0} == "\"")){
      $string = substr($string, 1);
      $string = substr($string, 0, -1);
    }
    else if($string && ($string{0} == "{"))
      {
	if(strlen($string) > 0 && $string[strlen($string)-1] == "}"){
	  $string = substr($string, 1);
	  $string = substr($string, 0, -1);
	}
      }
    return $string;
  }
	
  // Remove enclosures around entry field values.  Additionally, expand macros if flag set.
  function removeDelimitersAndExpand($string, $preamble = FALSE)
  {
    // 02/05/2005 G. Gardey
    $string = $this->removeDelimiters($string);
    $delimitersExist = $this->delimitersExist;
    // expand the macro if defined
    // 23/08/2004 Mark - changed isset() to !empty() since $this->strings isset in constructor.
    if($string && $this->expandMacro)
      {
	if(!empty($this->strings) && !$preamble)
	  {
	    // macro are case insensitive
	    foreach($this->strings as $key => $value)
	      {
		// 09/March/2005 - Mark Grimshaw - sometimes $key is empty - not sure why
		//			if(!$key || !$value || !$string)
		//				continue;
		if(!$delimitersExist)
		  $string = eregi_replace($key, $value, $string);
		// 22/08/2004 Mark Grimshaw - make sure a '#' surrounded by any number of spaces is replaced by just one space.
		// 30/04/2005 Mark Grimshaw - ensure entries such as journal = {{Journal of } # JRNL23} are properly parsed
		// 02/05/2005 G. Gardey - another solution for the previous line
		$items = split("#",$string);
		$string = "";
		foreach($items as $val){
		  $string .= $this->removeDelimiters(trim($val))." ";
		}
                    
		$string = preg_replace("/\s+/", " ", $string);
		//            				$string = str_replace('#',' ',$string);
	      }
	  }
	if(!empty($this->userStrings))
	  {
	    // 24/08/2004 G.Gardey replace user defined strings macro
	    foreach($this->userStrings as $key => $value)
	      {
		$string = eregi_replace($key,$value,$string);
		$string = preg_replace("/\s*#\s*/", " ", $string);
	      }
	  }
      }
    return $string;
  }

  // This method starts the whole process
  function extractEntries() {
    $lastLine = FALSE;
    if($this->parseFile)
      {
	while(!feof($this->fid))
	  {
	    $line = $lastLine ? $lastLine : $this->getLine();
	    if(!preg_match("/^@/i", $line))
	      continue;
	    if(($lastLine = $this->getEntry($line)) !== FALSE)
	      continue;
	  }
      }
    else{
      while($this->currentLine < count($this->bibtexString))
	{
	  $line = $lastLine ? $lastLine : $this->getLine();
	  if(!preg_match("/^@/i", $line))
	    continue;
	  if(($lastLine = $this->getEntry($line)) !== FALSE)
	    continue;
	}
    }
  }
  // Return arrays of entries etc. to the calling process.
  function returnArrays()
  {
    foreach($this->preamble as $value)
      {
	preg_match("/.*[{(](.*)/", $value, $matches);
	$preamble = substr($matches[1], 0, -1);
	$preambles['bibtexPreamble'] = trim($this->removeDelimitersAndExpand(trim($preamble), TRUE));
      }
    if(isset($preambles))
      $this->preamble = $preambles;
    if($this->fieldExtract)
      {
	foreach($this->strings as $value)
	  {
	    // changed 21/08/2004 G. Gardey
	    // 23/08/2004 Mark G. account for comments on same line as @string - count delimiters in string value
	    $value = trim($value);
	    $matches = preg_split("/@string\s*([{(])/i", $value, 2, PREG_SPLIT_DELIM_CAPTURE);
	    $delimit = $matches[1];
	    $matches = preg_split("/=/", $matches[2], 2, PREG_SPLIT_DELIM_CAPTURE);
	    $strings[trim($matches[0])] = trim($this->extractStringValue($matches[1]));
	  }
      }
    if(isset($strings))
      $this->strings = $strings;
        
    // changed 21/08/2004 G. Gardey
    // 22/08/2004 Mark Grimshaw - stopped useless looping.
    // removeDelimit and expandMacro have NO effect if !$this->fieldExtract
    if($this->removeDelimit || $this->expandMacro && $this->fieldExtract)
      {
	for($i = 0; $i < count($this->data); $i++)
	  {
	    if($this->data[$i]) {
	    foreach($this->data[$i] as $key => $value)
	      // 02/05/2005 G. Gardey don't expand macro for key 
	      // and entrytype
	      if($key != 'cite' && $key != 'entrytype'){
    
		$this->data[$i][$key] = trim($this->removeDelimitersAndExpand($this->data[$i][$key])); 
	      }
	    }
	  }
      }
    if(empty($this->preamble))
      $this->preamble = FALSE;
    if(empty($this->strings))
      $this->strings = FALSE;
    if(empty($this->data))
      $this->data = FALSE;
    return array($this->preamble, $this->strings, $this->data);
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
   * Extracting the data of one entry
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
