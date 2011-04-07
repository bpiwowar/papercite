<?
/**
 * This class is an entry format
 * @author Benjamin Piwowarski
 * @version 1.0
 */
class BibtexEntryFormat {
  var $formats = array();
  var $properties = array();

  function BibtexEntryFormat(&$file_content) {
    $parser = xml_parser_create(); 
    if (!$parser) 
      return false;
    xml_set_element_handler($parser, array($this, "start_element"), array($this, "end_element"));
    xml_set_character_data_handler ($parser,  array($this, "characters"));
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
    xml_parse($parser, $file_content);
    xml_parser_free($parser);
    $this->format = null;
  }

  function get($name) {
    if (array_key_exists($name, $this->formats))
      return $this->formats[$name];
    return $this->formats["#"];
  }

  function start_element($parser, $name, $att) {
    if ($name == "format") {
      $this->format = "";
      foreach(preg_split("#\\s+#",$att["types"]) as $type) {
	$this->formats[$type] = &$this->format;
      }
    } else if (!is_null($this->format)) {
      $this->format .= "<$name";
      foreach($att as $name => $value) {
	$this->format .= " $name='".  htmlspecialchars($value) ."'";
      }
      $this->format .= ">";
    } else if ($name == "property")  {
      $this->properties[$att["name"]] = $att["value"];
    } 
  }

  function end_element(&$parser, $name) {
    if ($name == "format") {
      unset($this->format);
    } else if (!is_null($this->format)) {
      $this->format .= "</$name>";
    }
  }

  function characters(&$parser, &$data) {
    if (!is_null($this->format))
      $this->format .= $data;
  }

  /**
   * This function takes an array of authors and renders is into a comma
   * separated list of authors. If no array is passed the value is returned
   * without change.
   *
   * @access public
   * @param array authors Value representing authors
   * @return string Either a string if an array was passed or input value
   *                otherwise.
   */
  function niceAuthors($authors)
  {
    // OSBib glue
    $delimitTwo = "primaryTwoCreatorsSep";
    $delimitFirstBetween = 'primaryCreatorSepFirstBetween';
    $delimitNextBetween = 'primaryCreatorSepNextBetween';
    $delimitLast = 'primaryCreatorSepNextLast';
    $style = &$this->properties;

    $etAl = false;
    $first = true;
    $cArray = array();
    foreach ( $authors as $key => $author )
      {
	$cArray[] = trim($this->niceAuthor($author, $first));	
	$first = false;
      }
    
    /**
     * Keep only some elements in array if we've exceeded $moreThan
     */
    $etAl = FALSE;
    if($style[$limit] && (sizeof($cArray) > $style[$moreThan]))
      {
        array_splice($cArray, $style[$limit]);
        if(isset($style[$italics]))
          $etAl = "<span style='font-style: italic'>" . $style[$abbreviation] . "</span>";
        else
          $etAl = $style[$abbreviation];
      }

    /**
     * add delimiters
     */
    if(sizeof($cArray) > 1)
      {
        if(sizeof($cArray) == 2)
          $cArray[0] .= $style[$delimitTwo];
        else
          {
            for($index = 0; $index < (sizeof($cArray) - 2); $index++)
              {
                if(!$index)
                  $cArray[$index] .= $style[$delimitFirstBetween];
                else
                  $cArray[$index] .= $style[$delimitNextBetween];
              }
            $cArray[sizeof($cArray) - 2] .= $style[$delimitLast];
          }
      }


    /**
     * Finally flatten array
     */
    if($etAl)
      $pString = implode('', $cArray) . $etAl;
    else
      $pString = implode('', $cArray);

    
    return $pString;
  }

  /**
   * This function takes an author and renders is into a string.
   * If no array is passed the value is returned without change.
   *
   * @access public
   * @param array authors Value representing authors
   * @return string Either a string if an array was passed or input value
   *               otherwise.
   */
  function niceAuthor($creator, $first = true)
  {

    $firstName = trim($this->checkInitials($creator, $this->properties["primaryCreatorInitials"], 
					   $this->properties["primaryCreatorFirstName"]));

    $prefix = $creator['prefix'] ? trim(stripslashes($creator['prefix'])) . ' ' : '';
    
    $style = $this->properties[$first ? "primaryCreatorFirstStyle" : "primaryCreatorOtherStyle"];

    if($style == 0) // Joe Bloggs
      {
	$nameString = $firstName . ' ' . 
	  $prefix . 
	  stripslashes($creator['surname']);
      }
    else if($style == 1) // Bloggs, Joe
      {
	$prefixDelimit = $firstName ? ', ' : '';
	$nameString = 
	  stripslashes($creator['prefix']) . ' ' . 
	  stripslashes($creator['surname']) . $prefixDelimit . 
	  $firstName;
      }
    else if($style == 2) // Bloggs Joe
      {
	$nameString = 
	  stripslashes($creator['prefix']) . ' ' . 
	  stripslashes($creator['surname']) . ' ' . 
	  $firstName;
      }
    else // Last name only
      {
	$nameString = 
	  stripslashes($creator['prefix']) . ' ' . 
	  stripslashes($creator['surname']);
      }
    
    return $nameString;
  }


  /**
   * Handle initials.
   * 
   * Taken from OSBib
   *
   * @see formatNames()
   * 
   * @author    Mark Grimshaw
   * @version   1
   * 
   * @param     $creator        Associative array of creator name e.g.
   * <pre>
   *    array(['surname'] => 'Grimshaw', ['firstname'] => Mark, ['initials'] => 'M N G', ['prefix'] => ))
   * </pre>
   * Initials must be space-delimited.
   *
   * @param     $initialsStyle
   * @param     $firstNameInitial
   * @return    Formatted string of initials.
   */
  function checkInitials(&$creator, $initialsStyle, $firstNameInitial)
  {
    /**
     * Format firstname
     */
    if($creator['firstname'] && !$firstNameInitial) // Full name
      $firstName = stripslashes($creator['firstname']);
    else if($creator['firstname']) // Initial only of first name.  'firstname' field may actually have several 'firstnames'
      {
        $fn = split(" ", stripslashes($creator['firstname']));
        $firstTime = TRUE;
        foreach($fn as $name)
          {
            if($firstTime)
              {
                $firstNameInitialMake = mb_strtoupper(mb_substr(trim($name), 0, 1));
                $firstTime = FALSE;
              }
            else
              $initials[] = mb_strtoupper(mb_substr(trim($name), 0, 1));
          }
        if(isset($initials))
          {
            if($creator['initials'])
              $creator['initials'] = join(" " , $initials) . ' ' . $creator['initials'];
            else
              $creator['initials'] = join(" " , $initials);
          }
      }
    /**
     * Initials are stored as space-delimited characters.
     * If no initials, return just the firstname or its initial in the correct format.
     */
    if(!$creator['initials'])
      {
        if(isset($firstName))   // full first name only
          return $firstName;
        if(isset($firstNameInitialMake) && $initialsStyle > 1) // First name initial with no '.'
          return $firstNameInitialMake;
        if(isset($firstNameInitialMake)) // First name initial with  '.'
          return $firstNameInitialMake . '.';
        return ''; // nothing here
      }
    $initialsArray = explode(' ', $creator['initials']);
    /**
     * If firstname is initial only, prepend to array
     */
    if(isset($firstNameInitialMake))
      array_unshift($initialsArray, $firstNameInitialMake);
    if($initialsStyle == 0) // 'T. U. '
      $initials = implode('. ', $initialsArray) . '.';
    else if($initialsStyle == 1) // 'T.U.'
      $initials = implode('.', $initialsArray) . '.';
    else if($initialsStyle == 2) // 'T U '
      $initials = implode(' ', $initialsArray);
    else // 'TU '
      $initials = implode('', $initialsArray);
    /**
     * If we have a full first name, prepend it to $initials.
     */
    if(isset($firstName))
      return ($firstName . ' ' . $initials);
    return $initials;
  }


}
?>