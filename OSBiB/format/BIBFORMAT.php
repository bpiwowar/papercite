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

  /** Description of class BIBFORMAT
   * Format a bibliographic resource for output.
   * 
   * @author	Mark Grimshaw
   * @version	1
   */
class BIBFORMAT
{
  /**
   * $dir is the path to STYLEMAP.php etc.
   */
  function BIBFORMAT($dir = FALSE, $bibtex = FALSE, $preview = FALSE)
  {
    //05/05/2005 G.GARDEY: add a last "/" to $stylePath if not present.
    $this->preview = $preview;
    if(!$this->preview) // Not javascript preview
      {
	$dir = trim($dir);
	if(!$dir){
	  $this->dir = dirname(__FILE__) . "/";
	}
	else{
	  $this->dir = $dir;
	  if($dir[strlen($dir)-1] != "/"){
	    $this->dir .= "/";
	  }
	}
	$this->bibtexParsePath  = $this->dir . "format/bibtexParse";
      }
    else // preview
      $this->dir = '';
    $this->bibtex = $bibtex;
    if($this->bibtex)
      {
	include_once($this->dir."STYLEMAPBIBTEX.php");
	$this->styleMap = new STYLEMAPBIBTEX();
      }
    else
      {
	include_once($this->dir."STYLEMAP.php");
	$this->styleMap = new STYLEMAP();
      }
    include_once($this->dir."UTF8.php");
    $this->utf8 = new UTF8();
    /**
     * Highlight preg pattern and CSS class for HTML display
     */
    $this->patterns = FALSE;
    $this->patternHighlight = FALSE;
    /**
     * Output medium:
     * Defaul 'html'
     */
    $this->output = 'html';
    $this->previousCreator = '';
    /**
     * Switch editor and author positions in the style definition for a book in which there are only editors
     */
    $this->editorSwitch = FALSE;
    /**
     * Load month arrays
     */
    $this->loadArrays();
    /**
     *  Convert the entry to produce utf8
     *  Defaut: 'FALSE', we assume that the entries are already clean
     */
    $this->convertEntry=FALSE;
  }
  /**
   * Read the chosen bibliographic style and create arrays based on resource type.
   * 
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$stylePath	The path where the styles are.
   * @param	$style		The requested bibliographic output style.
   * @return	BOOLEAN
   */
  function loadStyle($stylePath, $style)
  {
    //05/05/2005 G.GARDEY: add a last "/" to $stylePath if not present.
    $stylePath = trim($stylePath);
    if($stylePath[strlen($stylePath)-1] != "/"){
      $stylePath .= "/";
    }
    $uc = $stylePath . strtolower($style) . "/" . strtolower($style) . ".xml";
    $lc = $stylePath . strtolower($style) . "/" . strtoupper($style) . ".xml";
    $styleFile = file_exists($uc) ? $uc : $lc;
    if(!$fh = fopen($styleFile, "r"))
      return array(FALSE, FALSE, FALSE);
    include_once($this->dir."PARSEXML.php");
    $parseXML = new PARSEXML($this);
    list($info, $citation, $common, $types) = $parseXML->extractEntries($fh);
    fclose($fh);
    return array($info, $citation, $common, $types);
  }
  /**
   * Transform the raw data from the XML file into usable arrays
   *
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$common		Array of global formatting data
   * @param	$types		Array of style definitions for each resource type
   */
  function getStyle($common, $types)
  {
    $this->commonToArray($common);
    $this->typesToArray($types);
  }
  /**
   * Reformat the array representation of common styling into a more useable format.
   * 'common' styling refers to formatting that is common to all resource types such as creator formatting, title 
   * capitalization etc.
   *
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$common		nodal array representation of XML data
   * @return	flattened array representation for easier use.
   */
  function commonToArray($common)
  {
    foreach($common as $array)
      {
	if(array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array))
	  $this->style[$array['_NAME']] = $array['_DATA'];
      }
  }
  /**
   * Reformat the array representation of resource types into arrays based on the type.
   *
   * @param	$types		nodal array representation of XML data
   */
  function typesToArray($types)
  {
    foreach($types as $resourceArray)
      {
	/**
	 * The resource type which will be our array name
	 */
	$type = $resourceArray['_ATTRIBUTES']['name'];
	$styleDefinition = $resourceArray['_ELEMENTS'];
	foreach($styleDefinition as $array)
	  {
	    if(array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array) 
	       && array_key_exists('_ELEMENTS', $array))
	      {
		if($array['_NAME'] == 'fallbackstyle')
		  {
		    $this->fallback[$type] = $array['_DATA'];
		    break;
		  }
		if($array['_NAME'] == 'ultimate')
		  {
		    $this->{$type}['ultimate'] = $array['_DATA'];
		    continue;
		  }
		foreach($array['_ELEMENTS'] as $elements)
		  {
		    if($array['_NAME'] == 'independent')
		      {
			$split = split("_", $elements['_NAME']);
			$this->{$type}[$array['_NAME']][$split[1]] 
					= $elements['_DATA'];
		      }
		    else
		      $this->{$type}[$array['_NAME']][$elements['_NAME']] 
				      = $elements['_DATA'];
		  }
	      }
	  }
	/**
	 * Backup each $this->$type array.  If we need to switch editors, it's faster to restore each 
	 * $this->$type array from this backup than to reload the style file and parse it.
	 */
	if(isset($this->$type))
	  $this->backup[$type] = $this->$type;
      }
  }
  /**
   * Restore each $this->type array from $this->backup
   *
   * @author	Mark Grimshaw
   * @version	1
   */
  function restoreTypes()
  {
    foreach($this->backup as $type => $array)
      $this->$type = $array;
  }
  /**
   * Perform pre-processing on the raw SQL array
   *
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$type	The resource type
   * @param	$row	Associate array of raw SQL data
   * @return	$row	Processed row of raw SQL data
   */
  function preProcess($type, $row)
  {
    /**
     * Ensure that $this->item is empty for each resource!!!!!!!!!!
     */
    $this->item = array();
    // Map this system's resource type to OSBib's resource type
    $this->type = array_search($type, $this->styleMap->types);
    if($this->bibtex && array_key_exists('author', $row))
      {
	$row['creator1'] = $row['author'];
	unset($row['author']);
      }
    if($this->bibtex && array_key_exists('editor', $row))
      {
	$row['creator2'] = $row['editor'];
	unset($row['editor']);
      }
    /**
     * Set any author/editor re-ordering for book and book_article type.
     */
    if(!$this->preview && (($type == 'book') || ($type == 'book_article')) && 
       $row['creator2'] && !$row['creator1'] && $this->style['editorSwitch'] &&
       array_key_exists('author', $this->$type))
      {
	$row['creator1'] = $row['creator2'];
	$row['creator2'] = FALSE;
	include_once($this->dir . "PARSESTYLE.php");
	$editorArray = PARSESTYLE::parseStringToArray($type, $this->style['editorSwitchIfYes'], 
						      $this->styleMap);
	if(!empty($editorArray) && array_key_exists('editor', $editorArray))
	  {
	    $this->{$type}['author'] = $editorArray['editor'];
	    unset($this->{$type}['editor']);
	    $this->editorSwitch = TRUE;
	  }
      }
    /**
     * If $row comes in in BibTeX format, process and add items to $this->item
     */
    if($this->bibtex) 
      {
	if(!$this->type)
	  {
	    list($type, $row) = $this->preProcessBibtex($row, $type);
	  } else 
	  list($type, $row) = $this->preProcessBibtex($row, $this->type);
      }
    /**
     * Ensure that for theses types, the first letter of type and label are capitalized (e.g. 'Master's Thesis').
     */
    if($type == 'thesis')
      {
	if(($key = array_search('type', $this->styleMap->$type)) !== FALSE)
	  {
	    if(isset($row[$key]))
	      $row[$key] = ucfirst($row[$key]);
	  }
	if(($key = array_search('label', $this->styleMap->$type)) !== FALSE)
	  {
	    if(isset($row[$key]))
	      $row[$key] = ucfirst($row[$key]);
	  }
      }
    /**
     * Set to catch-all generic style.  For all keys except named database fields, creator1 and year1, 
     * we only print if the value in $this->styleMap matches the value in 
     * $this->styleMap->generic for each key.
     */
    if(!isset($this->$type))
      {
	$fallback = $this->fallback[$type];
	$type = $fallback;
      }
    $this->type = $type;
    /**
     * Add BibTeX entry to $this->item
     */
    if($this->bibtex)
      {
	foreach($row as $field => $value)
	  {
	    if(array_key_exists($field, $this->styleMap->$type) && 
	       !array_key_exists($this->styleMap->{$type}[$field], $this->item))
	      $this->addItem($row[$field], $field);
	  }
      }
    return $row;
  }
  /**
   * Preprocess BibTeX-type entries
   * @author Mark Grimshaw
   * @version 1
   *
   * @param assoc. array of elements for one bibtex entry
   * @param string resource type
   * @return string resource type
   * @return array resource assoc. array of elements for one bibtex entry
   */
  function preProcessBibtex(&$row, $type)
  {
    //05/05/2005 G.GARDEY: change bibtexParse name.
    /**
     * This set of includes is for the OSBib public release and should be uncommented for that and
     * the WIKINDX-specific includes below commented out!
     */
    include_once($this->bibtexParsePath . "/PARSECREATORS.php");
    $parseCreator = new PARSECREATORS();
    include_once($this->bibtexParsePath . "/PARSEMONTH.php");
    $parseDate = new PARSEMONTH();
    include_once($this->bibtexParsePath . "/PARSEPAGE.php");
    $parsePages = new PARSEPAGE();

    // WIKINDX naming of above files
    /*
     include_once($this->bibtexParsePath . "/BIBTEXCREATORPARSE.php");
     $parseCreator = new BIBTEXCREATORPARSE();
     include_once($this->bibtexParsePath . "/BIBTEXMONTHPARSE.php");
     $parseDate = new BIBTEXMONTHPARSE();
     include_once($this->bibtexParsePath . "/BIBTEXPAGEPARSE.php");
     $parsePages = new BIBTEXPAGEPARSE();



    */
    // Added by Christophe Ambroise: convert the bibtex entry to utf8 (for storage or printing)
    if ($this->cleanEntry) {$row=$this->convertEntry($row);}
    //


    /**
     * Bibtex-specific types not defined in STYLEMAPBIBTEX
     */

    if(!$this->type)
      {
	if($type == 'mastersthesis')
	  {
	    $type = 'thesis';
	    $row['type'] = "Master's Dissertation";
	  }
	if($type == 'phdthesis')
	  {
	    $type = 'thesis';
	    $row['type'] = "PhD Thesis";
	  }
	else if($type == 'booklet')
	  $type = 'miscellaneous';
	else if($type == 'conference')
	  $type = 'proceedings_article';
	else if($type == 'incollection')
	  $type = 'book_article';
	else if($type == 'manual')
	  $type = 'report';
      }
    /**
     * 'article' could be journal, newspaper or magazine article
     */
    else if($type == 'journal_article')
      {
	if(array_key_exists('month', $row) && array_key_exists('date', $this->styleMap->$type))
	  {
	    list($startMonth, $startDay, $endMonth, $endDay) = $parseDate->init($row['month']);
	    if($startDay)
	      $type = 'newspaper_article';
	    else if($startMonth)
	      $type = 'magazine_article';
	    $this->formatDate($startDay, $startMonth, $endDay, $endMonth);
	  }
	else
	  $type = 'journal_article';
      }
    /**
     * Is this a web article?
     */
    else if(($type == 'miscellaneous') && array_key_exists('howpublished', $row))
      {
	if(preg_match("#^\\\url{(.*://.*)}#", $row['howpublished'], $match))
	  {
	    $row['URL'] = $match[1];
	    $type = 'web_article';
	  }
      }
    $this->type = $type;
    if(array_key_exists('creator1', $row) && $row['creator1'] && 
       array_key_exists('creator1', $this->styleMap->$type))
      {
	$creators = $parseCreator->parse($row['creator1']);
	foreach($creators as $cArray)
	  {
	    $temp[] = array(
			    'surname'	=>	trim($cArray[2]),
			    'firstname'	=>	trim($cArray[0]),
			    'initials'	=>	trim($cArray[1]),
			    'prefix'	=>	trim($cArray[3]),
			    );
	  }
	$this->formatNames($temp, 'creator1');
	unset($temp);
      }
    if(array_key_exists('creator2', $row) && $row['creator2'] && 
       array_key_exists('creator2', $this->styleMap->$type))
      {
	$creators = $parseCreator->parse($row['creator2']);
	foreach($creators as $cArray)
	  {
	    $temp[] = array(
			    'surname'	=>	trim($cArray[2]),
			    'firstname'	=>	trim($cArray[0]),
			    'initials'	=>	trim($cArray[1]),
			    'prefix'	=>	trim($cArray[3]),
			    );
	  }
	$this->formatNames($temp, 'creator2');
      }
    if(array_key_exists('pages', $row) && array_key_exists('pages', $this->styleMap->$type))
      {
	list($start, $end) = $parsePages->init($row['pages']);
	$this->formatPages(trim($start), trim($end));
      }
    $this->formatTitle($row['title'], "{", "}");
    return array($type, $row);
  }
  /**
   * Map the $item array against the style array ($this->$type) for this resource type and produce a string ready to be 
   * formatted for bold, italics etc.
   * 
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$template	If called from CITEFORMAT, this is the array of template elements.
   * @return	string ready for printing to the output medium.
   */
  function map($template = FALSE)
  {
    /**
     * Output medium:
     * 'html', 'rtf', or 'plain'
     */
    include_once($this->dir . "format/EXPORTFILTER.php");
    $this->export = new EXPORTFILTER($this, $this->output);
    if($template)
      {
	$this->citation = $template;
	$this->type = 'citation';
      }
    $type = $this->type;
    $ultimate = '';
    $index = 0;
    $previousFieldExists = $nextFieldExists = TRUE;
    if(array_key_exists('independent', $this->$type))
      $independent = $this->{$type}['independent'];
    /**
     * For dependency on next field, we must grab array keys of $this->$type, shift the first element then, in the loop, 
     * check each element exists in $item.  If it doesn't, $nextFieldExists is set to FALSE
     */
    $checkPost = array_keys($this->$type);
    array_shift($checkPost);
    foreach($this->$type as $key => $value)
      {
	if($key == 'ultimate')
	  {
	    $ultimate = $value;
	    continue;
	  }
	if(!array_key_exists($key, $this->item) || !$this->item[$key])
	  {
	    $keyNotExists[] = $index;
	    $index++;
	    array_shift($checkPost);
	    $previousFieldExists = FALSE;
	    continue;
	  }
	$checkPostShift = array_shift($checkPost);
	if(!array_key_exists($checkPostShift, $this->item) || !$this->item[$checkPostShift])
	  $nextFieldExists = FALSE;
	$pre = array_key_exists('pre', $value) ? $value['pre'] : '';
	$post = array_key_exists('post', $value) ? $value['post'] : '';
	/**
	 * Deal with __DEPENDENT_ON_PREVIOUS_FIELD__ for characters dependent on previous field's existence and 
	 * __DEPENDENT_ON_NEXT_FIELD__ for characters dependent on the next field's existence
	 */
	if($previousFieldExists && array_key_exists('dependentPre', $value))
	  $pre = preg_replace("/__DEPENDENT_ON_PREVIOUS_FIELD__/", 
			      $value['dependentPre'], $pre);
	else if(array_key_exists('dependentPreAlternative', $value))
	  $pre = preg_replace("/__DEPENDENT_ON_PREVIOUS_FIELD__/", 
			      $value['dependentPreAlternative'], $pre);
	else
	  $pre = preg_replace("/__DEPENDENT_ON_PREVIOUS_FIELD__/", '', $pre);
	if($nextFieldExists && array_key_exists('dependentPost', $value))
	  $post = str_replace("__DEPENDENT_ON_NEXT_FIELD__", 
			      $value['dependentPost'], $post);
	else if(array_key_exists('dependentPostAlternative', $value))
	  $post = preg_replace("/__DEPENDENT_ON_NEXT_FIELD__/", 
			       $value['dependentPostAlternative'], $post);
	else
	  $post = preg_replace("/__DEPENDENT_ON_NEXT_FIELD__/", '', $post);
	/**
	 * Deal with __SINGULAR_PLURAL__ for creator lists and pages
	 */			if($styleKey = array_search($key, $this->styleMap->$type))
	  $pluralKey = $styleKey . "_plural";
	if(isset($this->$pluralKey) && $this->$pluralKey) // plural alternative for this key
	  {
	    $pre = array_key_exists('plural', $value) ? 
	      preg_replace("/__SINGULAR_PLURAL__/", $value['plural'], $pre) : $pre;
	    $post = array_key_exists('plural', $value) ? 
	      preg_replace("/__SINGULAR_PLURAL__/", $value['plural'], $post) : $post;
	  }
	else if(isset($this->$pluralKey)) // singular alternative for this key
	  {
	    $pre = array_key_exists('singular', $value) ? 
	      preg_replace("/__SINGULAR_PLURAL__/", $value['singular'], $pre) : $pre;
	    $post = array_key_exists('singular', $value) ? 
	      preg_replace("/__SINGULAR_PLURAL__/", $value['singular'], $post) : $post;
	  }
	/**
	 * Make sure we don't have duplicate punctuation characters
	 */			$lastPre = substr($post, -1);
	$firstItem = substr($this->item[$key], 0, 1);
	if($firstItem === $lastPre)
	  $this->item[$key] = substr($this->item[$key], 1);
	$firstPost = substr($post, 0, 1);
	$lastItem = substr($this->item[$key], -1);
	//			if(preg_match("/\.|,|;|:\?!/", $lastItem) && preg_match("/\.|,|;|:|\?|!/", $firstPost))
	if(preg_match("/\.|,|;|:\?!/", $lastItem) && ($firstPost == $lastItem))
	  $post = substr($post, 1); // take a guess at removing first character of $post
	/**
	 * Strip backticks used in template
	 */
	$pre = str_replace("`", '', $pre);
	$post = str_replace("`", '', $post);
	$pre = ($this->output == 'html') ? $this->utf8->utf8_htmlspecialchars($pre) : $pre;
	$post = ($this->output == 'html') ? $this->utf8->utf8_htmlspecialchars($post) : $post;
	if($this->item[$key])
	  $itemArray[$index] = $pre . $this->item[$key] . $post;
	$previousFieldExists = $nextFieldExists = TRUE;
	$index++;
      }
    /**
     * Check for independent characters.  These (should) come in pairs.
     */		if(isset($independent))
      {
	$independentKeys = array_keys($independent);
	while($independent)
	  {
	    $preAlternative = $postAlternative = FALSE;
	    $startFound = $endFound = FALSE;
	    $pre = array_shift($independent);
	    $post = array_shift($independent);
	    if(preg_match("/%(.*)%(.*)%|%(.*)%/U", $pre, $dependent))
	      {
		if(sizeof($dependent) == 4)
		  $pre = $dependent[3];
		else
		  {
		    $pre = $dependent[1];
		    $preAlternative = $dependent[2];
		  }
	      }
	    if(preg_match("/%(.*)%(.*)%|%(.*)%/U", $post, $dependent))
	      {
		if(sizeof($dependent) == 4)
		  $post = $dependent[3];
		else
		  {
		    $post = $dependent[1];
		    $postAlternative = $dependent[2];
		  }
	      }
	    /**
	     * Strip backticks used in template
	     */
	    $preAlternative = str_replace("`", '', $preAlternative);
	    $postAlternative = str_replace("`", '', $postAlternative);
	    $firstKey = array_shift($independentKeys);
	    $secondKey = array_shift($independentKeys);
	    for($index = $firstKey; $index <= $secondKey; $index++)
	      {
		if(array_key_exists($index, $itemArray))
		  {
		    $startFound = $index;
		    break;
		  }
	      }
	    for($index = $secondKey; $index >= $firstKey; $index--)
	      {
		if(array_key_exists($index, $itemArray))
		  {
		    $endFound = $index;
		    break;
		  }
	      }
	    if(($startFound !== FALSE) && ($endFound !== FALSE)) // intervening fields found
	      {
		$itemArray[$startFound] = $pre . $itemArray[$startFound];
		$itemArray[$endFound] = $itemArray[$endFound] . $post;
	      }
	    else // intervening fields not found - do we have an alternative?
	      {
		if(array_key_exists($firstKey - 1, $itemArray) && $preAlternative)
		  $itemArray[$firstKey - 1] .= $preAlternative;
		if(array_key_exists($secondKey + 1, $itemArray) && $postAlternative)
		  $itemArray[$secondKey + 1] = $postAlternative . 
		    $itemArray[$secondKey + 1];
	      }
	  }
      }
    $pString = join('', $itemArray);
    /**
     * if last character is punctuation (which it may be with missing fields etc.), and $ultimate is also 
     * punctuation, remove last character.
     */		if(isset($ultimate) && $ultimate)
      {
	$last = substr(trim($pString), -1);
	/**
	 * Don't do ';' in case last element is URL with &gt; ...!
	 */
	if(preg_match("/^\.|^,||^:^\?^\!/", $ultimate) && preg_match("/\.|,|:|\?|!/", $last))
	  $pString = substr(trim($pString), 0, -1);
      }
    // If $this->editorSwitch, we have altered $this->$bibformat->$type so need to reload styles
    if($this->editorSwitch)
      {
	$this->restoreTypes();
	$this->editorSwitch = FALSE;
      }
    return $this->export->format(trim($pString) . $ultimate);
  }
  /**
   * Format creator name lists (authors, editors, etc.)
   * 
   * @author	Mark Grimshaw
   * @version	1
   * 
   * @param	$creators	Multi-associative array of creator names e.g. this array might be of 
   * the primary authors:
   * <pre>
   *	array([0] => array(['surname'] => 'Grimshaw', ['firstname'] => Mark, ['initials'] => 'N', ['prefix'] => ),
   *	   [1] => array(['surname'] => 'Witt', ['firstname'] => Jan, ['initials'] => , ['prefix'] => 'de'))
   * </pre>
   * @param	$nameType	'creator1', 'creator2' etc.  If $nameType == 'citation', this method is called 
   * from CITEFORMAT for formatting citation creators in which case we expect the 3rd parameter $citation.
   * @param	$citation	If called from CITEFORMAT, this is the array of citation stylings.
   * @return	Optional if $nameType == 'citation': formatted string of all creator names in the input array.
   */
  function formatNames($creators, $nameType, $citation = FALSE)
  {
    $style = $citation ? $citation : $this->style;
    $first = TRUE;
    /**
     * Set default plural behaviour for creator lists
     */
    $pluralKey = $nameType . "_plural";
    $this->$pluralKey = FALSE;
    //		$this->creator1_plural = $this->creator2_plural = 
    //			$this->creator3_plural = $this->creator4_plural = $this->creator5_plural = FALSE;
    /**
     * Citation creators
     */
    if($nameType == 'citation')
      {
	$limit = 'creatorListLimit';
	$moreThan = 'creatorListMore';
	$abbreviation = 'creatorListAbbreviation';
	$initialsStyle = 'creatorInitials';
	$firstNameInitial = 'creatorFirstName';
	$delimitTwo = 'twoCreatorsSep';
	$delimitFirstBetween = 'creatorSepFirstBetween';
	$delimitNextBetween = 'creatorSepNextBetween';
	$delimitLast = 'creatorSepNextLast';
	$uppercase = 'creatorUppercase';
	$italics = 'creatorListAbbreviationItalic';
	if($first)
	  $nameStyle = 'creatorStyle';
	else
	  $nameStyle = 'creatorOtherStyle';
      }
    /**
     * Primary creator
     */
    else if($nameType == 'creator1')
      {
	$limit = 'primaryCreatorListLimit';
	$moreThan = 'primaryCreatorListMore';
	$abbreviation = 'primaryCreatorListAbbreviation';
	$initialsStyle = 'primaryCreatorInitials';
	$firstNameInitial = 'primaryCreatorFirstName';
	$delimitTwo = 'primaryTwoCreatorsSep';
	$delimitFirstBetween = 'primaryCreatorSepFirstBetween';
	$delimitNextBetween = 'primaryCreatorSepNextBetween';
	$delimitLast = 'primaryCreatorSepNextLast';
	$uppercase = 'primaryCreatorUppercase';
	$italics = 'primaryCreatorListAbbreviationItalic';
	if($first)
	  $nameStyle = 'primaryCreatorFirstStyle';
	else
	  $nameStyle = 'primaryCreatorOtherStyle';
      }
    else
      {
	$limit = 'otherCreatorListLimit';
	$moreThan = 'otherCreatorListMore';
	$abbreviation = 'otherCreatorListAbbreviation';
	$initialsStyle = 'otherCreatorInitials';
	$firstNameInitial = 'otherCreatorFirstName';
	$delimitTwo = 'otherTwoCreatorsSep';
	$delimitFirstBetween = 'otherCreatorSepFirstBetween';
	$delimitNextBetween = 'otherCreatorSepNextBetween';
	$delimitLast = 'otherCreatorSepNextLast';
	$uppercase = 'otherCreatorUppercase';
	$italics = 'otherCreatorListAbbreviationItalic';
	if($first)
	  $nameStyle = 'otherCreatorFirstStyle';
	else
	  $nameStyle = 'otherCreatorOtherStyle';
      }
    $type = $this->type;
    foreach($creators as $creator)
      {
	$firstName = trim($this->checkInitials($creator, $style[$initialsStyle], 
					       $style[$firstNameInitial]));
	$prefix = $creator['prefix'] ? trim(stripslashes($creator['prefix'])) . ' ' : '';
	if($style[$nameStyle] == 0) // Joe Bloggs
	  {
	    $nameString = $firstName . ' ' . 
	      $prefix . 
	      stripslashes($creator['surname']);
	  }
	else if($style[$nameStyle] == 1) // Bloggs, Joe
	  {
	    $prefixDelimit = $firstName ? ', ' : '';
	    $nameString = 
	      stripslashes($creator['prefix']) . ' ' . 
	      stripslashes($creator['surname']) . $prefixDelimit . 
	      $firstName;
	  }
	else if($style[$nameStyle] == 2) // Bloggs Joe
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
	if(isset($style[$uppercase]))
	  $nameString = $this->utf8->utf8_strtoupper($nameString);
	$cArray[] = trim($nameString);
	$first = FALSE;
      }
    /**
     * Keep only some elements in array if we've exceeded $moreThan
     */
    $etAl = FALSE;
    if($style[$limit] && (sizeof($cArray) > $style[$moreThan]))
      {
	array_splice($cArray, $style[$limit]);
	if(isset($style[$italics]))
	  $etAl = "[i]" . $style[$abbreviation] . "[/i]";
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
     * If sizeof of $cArray > 1 or $etAl != FALSE, set this $nameType_plural to TRUE
     */
    if((sizeof($cArray) > 1) || $etAl)
      {
	$pluralKey = $nameType . "_plural";
	$this->$pluralKey = TRUE;
      }
    /**
     * Finally flatten array
     */
    if($etAl)
      $pString = implode('', $cArray) . $etAl;
    else
      $pString = implode('', $cArray);
    /**
     * Check for repeating primary creator list in subsequent bibliographic item.
     */
    if($nameType == 'creator1')
      {
	$tempString = $pString;
	if(($style['primaryCreatorRepeat'] == 2) && ($this->previousCreator == $pString))
	  $pString = $style['primaryCreatorRepeatString'];
	else if(($style['primaryCreatorRepeat'] == 1) && 
		($this->previousCreator == $pString))
	  $pString = ''; // don't print creator list
	$this->previousCreator = $tempString;
      }
    else if($nameType == 'citation')
      return $pString;
    $this->item[$this->styleMap->{$type}[$nameType]] = $pString;
  }
  /**
   * Handle initials.
   * @see formatNames()
   * 
   * @author	Mark Grimshaw
   * @version	1
   * 
   * @param	$creator	Associative array of creator name e.g.
   * <pre>
   *	array(['surname'] => 'Grimshaw', ['firstname'] => Mark, ['initials'] => 'M N G', ['prefix'] => ))
   * </pre>
   * Initials must be space-delimited.
   *
   * @param	$initialsStyle
   * @param	$firstNameInitial
   * @return	Formatted string of initials.
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
		$firstNameInitialMake = $this->utf8->utf8_strtoupper($this->utf8->utf8_substr(trim($name), 0, 1));
		$firstTime = FALSE;
	      }
	    else
	      $initials[] = $this->utf8->utf8_strtoupper($this->utf8->utf8_substr(trim($name), 0, 1));
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
	if(isset($firstName))	// full first name only
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
  /**
   * Add an item to $this->item array
   *
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$item		The item to be added.
   * @param	$fieldName	The database fieldName of the item to be added
   */
  function addItem($item, $fieldName)
  {
    $type = $this->type;
    if($item === FALSE)
      return;
    /**
     * This item may already exist (e.g. edition field for WIKINDX)
     */
    if(isset($this->item) && array_key_exists($this->styleMap->{$type}[$fieldName], $this->item))
      return FALSE;
    $this->item[$this->styleMap->{$type}[$fieldName]] = $item;
  }
  /**
   * Add all remaining items to $this->item array
   *
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$row		The items to be added.
   */
  function addAllOtherItems($row)
  {
    $type = $this->type;
    foreach($row as $field => $value)
      {
	if(array_key_exists($field, $this->styleMap->$type) && 
	   !array_key_exists($this->styleMap->{$type}[$field], $this->item))
	  $this->addItem($row[$field], $field);
      }
  }
  /**
   * Format a title.  Anything enclosed in $delimitLeft...$delimitRight is to be left unchanged
   *
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$pString	Raw title string.
   * @param	$delimitLeft
   * @param	$delimitRight
   * @return	Formatted title string.
   */
  function formatTitle($pString, $delimitLeft = FALSE, $delimitRight = FALSE)
  {
    if(!$delimitLeft)
      $delimitLeft = '{';
    if(!$delimitRight)
      $delimitRight = '}';
    $delimitLeft = preg_quote($delimitLeft);
    $delimitRight = preg_quote($delimitRight);
    $match = "/" . $delimitLeft . "/";
    $type = $this->type;
    if(!array_key_exists('title', $this->styleMap->$type))
      $this->item[$this->styleMap->{$type}['title']] = '';
    /**
     * '0' == 'Osbib Bibliographic Formatting'
     * '1' == 'Osbib bibliographic formatting'
     */
    if($this->style['titleCapitalization'])
      {
	// Something here (preg_split probably) interferes with UTF-8 encoding (data is stored in 
	// the database as UTF-8 as long as web browser charset == UTF-8).  
	// So first decode then encode back to UTF-8 at end.
	// There is a 'u' UTF-8 parameter for preg_xxx but it doesn't work.
	$pString = $this->utf8->decodeUtf8($pString);
	$newString = '';
	while(preg_match($match, $pString))
	  {
	    $array = preg_split("/(.*)$delimitLeft(.*)$delimitRight(.*)/U", 
				$pString, 2, PREG_SPLIT_DELIM_CAPTURE);
	    /**
	     * in case user has input {..} incorrectly
	     */
	    if(sizeof($array) == 1)
	      break;
	    $newString .= $this->utf8->utf8_strtolower($this->utf8->encodeUtf8($array[1])) . $array[2];
	    $pString = $array[4];
	  }
	$newString .= $this->utf8->utf8_strtolower($this->utf8->encodeUtf8($pString));
      }
    $pString = isset($newString) ? $newString : $pString;
    $title = $this->utf8->encodeUtf8($this->utf8->utf8_ucfirst(trim($pString)));
    $this->item[$this->styleMap->{$type}['title']] =
      ($this->output == 'html') ? $this->utf8->utf8_htmlspecialchars($title) : $title;
  }
  /**
   * Format pages.
   * $this->style['pageFormat']:
   * 0 == 132-9
   * 1 == 132-39
   * 2 == 132-139
   * 
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$start		Page start.
   * @param	$end		Page end.
   * @param	$citation	If called from CITEFORMAT, this is the array of citation stylings.
   * @return	string of pages.
   */
  function formatPages($start, $end = FALSE, $citation = FALSE)
  {
    $type = $this->type;
    $style = $citation ? $citation : $this->style;
    /**
     * Set default plural behaviour for pages
     */
    $this->pages_plural = FALSE;
    /**
     * If no page end, return just $start;
     */
    if(!$end)
      {
	$this->item[$this->styleMap->{$type}['pages']] = $start;
	return;
      }
    /**
     * Pages may be in roman numeral format etc.  Return unchanged
     */
    if(!is_numeric($start))
      {
	$this->item[$this->styleMap->{$type}['pages']] = $start . '-' . $end;
	return;
      }
    /**
     * We have multiple pages...
     */
    $this->pages_plural = TRUE;
    /**
     * They've done something wrong so give them back exactly what they entered
     */
    if(($end <= $start) || (strlen($end) < strlen($start)))
      {
	$this->item[$this->styleMap->{$type}['pages']] = $start . '-' . $end;
	return;
      }
    else if($style['pageFormat'] == 2)
      {
	$this->item[$this->styleMap->{$type}['pages']] = $start . '-' . $end;
	return;
      }
    else
      {
	/**
	 * We assume page numbers are not into the 10,000 range - if so, return the complete pages
	 */
	if(strlen($start) <= 4)
	  {
	    $startArray = preg_split('//', $start);
	    array_shift($startArray); // always an empty element at start?
	    array_pop($startArray); // always an empty array element at end?
	    if($style['pageFormat'] == 0)
	      {
		array_pop($startArray);
		$endPage = substr($end, -1);
		$index = -2;
	      }
	    else
	      {
		array_pop($startArray);
		array_pop($startArray);
		$endPage = substr($end, -2);
		$index = -3;
	      }
	    while(!empty($startArray))
	      {
		$startPop = array_pop($startArray);
		$endSub = substr($end, $index--, 1);
		if($endSub == $startPop)
		  {
		    $this->item[$this->styleMap->{$type}['pages']] 
		      = $start . '-' . $endPage;
		    return;
		  }
		if($endSub > $startPop)
		  $endPage = $endSub . $endPage;
	      }
	  }
	else
	  {
	    $this->item[$this->styleMap->{$type}['pages']] = $start . '-' . $end;
	    return;
	  }
      }
    /**
     * We should never reach here - in case we do, give back complete range so that something at least is printed
     */
    $this->item[$this->styleMap->{$type}['pages']] = $start . '-' . $end;
  }
  /**
   * Format runningTime for film/broadcast
   *
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$minutes
   * @param	$hours
   */
  function formatRunningTime($minutes, $hours)
  {
    $type = $this->type;
    if($this->style['runningTimeFormat'] == 0) // 3'45"
      {
	if(isset($minutes) && $minutes)
	  {
	    if($minutes < 10)
	      $minutes = '0' . $minutes;
	    $runningTime = $hours . "'" . $minutes . "\"";
	  }
	else
	  $runningTime = $hours . "'00\"";
      }
    else if($this->style['runningTimeFormat'] == 1) // 3:45
      {
	if(isset($minutes) && $minutes)
	  {
	    if($minutes < 10)
	      $minutes = '0' . $minutes;
	    $runningTime = $hours . ":" . $minutes;
	  }
	else
	  $runningTime = $hours . ":00";
      }
    else if($this->style['runningTimeFormat'] == 1) // 3,45
      {
	if(isset($minutes) && $minutes)
	  {
	    if($minutes < 10)
	      $minutes = '0' . $minutes;
	    $runningTime = $hours . "," . $minutes;
	  }
	else
	  $runningTime = $hours . ",00";
      }
    else if($this->style['runningTimeFormat'] == 3) // 3 hours, 45 minutes
      {
	$hours = ($hours == 1) ? $hours . " hour" : $hours . " hours";
	if(isset($minutes) && $minutes)
	  {
	    $minutes = ($minutes == 1) ? $minutes . " minute" : $minutes . " minutes";
	    $runningTime = $hours . ", " . $minutes;
	  }
	else
	  $runningTime = $hours;
      }
    else if($this->style['runningTimeFormat'] == 4) // 3 hours and 45 minutes
      {
	$hours = ($hours == 1) ? $hours . " hour" : $hours . " hours";
	if(isset($minutes) && $minutes)
	  {
	    $minutes = ($minutes == 1) ? $minutes . " minute" : $minutes . " minutes";
	    $runningTime = $hours . " and " . $minutes;
	  }
	else
	  $runningTime = $hours;
      }
    $this->item[$this->styleMap->{$type}['runningTime']] = $runningTime;
  }
  /**
   * Format date
   *
   * @author	Mark Grimshaw
   * @version	2
   *
   * @param	INT $startDay
   * @param	INT $startMonth
   * @param	INT $endDay
   * @param	INT $sendMonth
   */
  function formatDate($startDay, $startMonth, $endDay, $endMonth)
  {
    $type = $this->type;
    if($startDay !== FALSE)
      {
	if($this->style['dayFormat']) // e.g. 10th
	  $startDay = $this->cardinalToOrdinal($startDay);
	else if($startDay < 10)
	  $startDay = '0' . $startDay;
      }
    if($endDay !== FALSE)
      {
	if($this->style['dayFormat']) // e.g. 10th
	  $endDay = $this->cardinalToOrdinal($endDay);
	else if($endDay < 10)
	  $endDay = '0' . $endDay;
      }
    if($this->style['monthFormat'] == 1) // Full month name
      $monthArray = $this->longMonth;
    else if($this->style['monthFormat'] == 2) // User-defined
      {
	for($i = 1; $i <= 12; $i++)
	  $monthArray[$i] = $this->style["userMonth_$i"];
      }
    else // Short month name
      $monthArray = $this->shortMonth;
    if($startMonth !== FALSE)
      $startMonth = $monthArray[$startMonth];
    if($endMonth !== FALSE)
      $endMonth = $monthArray[$endMonth];
    if(!$endMonth)
      {
	if($this->style['dateFormat']) // Order == Month Day
	  {
	    $startDay = ($startDay === FALSE) ? '' : ' ' . $startDay;
	    $date = $startMonth . $startDay;
	  }
	else // Order == Day Month
	  {
	    $startDay = ($startDay === FALSE) ? '' : $startDay . ' ';
	    $date = $startDay . $startMonth;
	  }
      }
    else // date range
      {
	if(!$startDay)
	  $delimit = $this->style['dateRangeDelimit2'];
	else
	  $delimit = $this->style['dateRangeDelimit1'];
	if(($endMonth !== FALSE) && ($startMonth == $endMonth) && ($this->style['dateRangeSameMonth'] == 1))
	  {
	    $endMonth = FALSE;
	    if(!$endDay)
	      $delimit = FALSE;
	  }
	if($this->style['dateFormat']) // Order == Month Day
	  {
	    $startDay = ($startDay === FALSE) ? '' : ' ' . $startDay;
	    $startDate = $startMonth . $startDay;
	    if($endMonth)
	      $endDate = $endMonth . $endDay = ($endDay === FALSE) ? '' : ' ' . $endDay;
	    else
	      $endDate = $endDay;
	  }
	else // Order == Day Month
	  {
	    if($endMonth)
	      {
		$startDate = $startDay . ' ' . $startMonth;
		$endDate = $endDay = ($endDay === FALSE) ? '' : $endDay . ' ';
		$endDate .= $endMonth;
	      }
	    else
	      {
		$startDate = $startDay;
		$endDate = ($endDay === FALSE) ? ' ' : $endDay . ' ';
		$endDate .= $startMonth;
	      }
	  }
	$date = $startDate . $delimit . $endDate;
      }
    $this->item[$this->styleMap->{$type}['date']] = $date;
  }
  /**
   * Format edition
   *
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	INT $edition
   * @return	string of edition.
   */
  function formatEdition($edition)
  {
    $type = $this->type;
    if(!is_numeric($edition))
      $edition = ($this->output == 'html') ? $this->utf8->utf8_htmlspecialchars($edition) : $edition;
    else if($this->style['editionFormat']) // 10th
      $edition = ($this->output == 'html') ? $this->utf8->utf8_htmlspecialchars($this->cardinalToOrdinal($edition)) 
	: $this->cardinalToOrdinal($edition);
    $this->item[$this->styleMap->{$type}[array_search('edition', $this->styleMap->$type)]] = $edition;
  }
  /**
   * Create ordinal number from cardinal
   *
   * @author	Mark Grimshaw
   * @version	1
   *
   * @param	$cardinal
   * @return	$ordinal
   */
  function cardinalToOrdinal($cardinal)
  {
    $modulo = $cardinal % 100;
    if(($modulo == 11) || ($modulo == 12) || ($modulo == 13))
      return $cardinal . 'th';
    $modulo = $cardinal % 10;
    if(($modulo >= 4) || !$modulo)
      return $cardinal . 'th';
    if($modulo == 1)
      return $cardinal . 'st';
    if($modulo == 2)
      return $cardinal . 'nd';
    if($modulo == 3)
      return $cardinal . 'rd';
  }
  /**
   * Month arrays
   * @author	Mark Grimshaw
   * @version	1
   */
  function loadArrays()
  {
    $this->longMonth = array(
			     1	=>	'January',
			     2	=>	'February',
			     3	=>	'March',
			     4	=>	'April',
			     5	=>	'May',
			     6	=>	'June',
			     7	=>	'July',
			     8	=>	'August',
			     9	=>	'September',
			     10	=>	'October',
			     11	=>	'November',
			     12	=>	'December',
			     );
    $this->shortMonth = array(
			      1	=>	'Jan',
			      2	=>	'Feb',
			      3	=>	'Mar',
			      4	=>	'Apr',
			      5	=>	'May',
			      6	=>	'Jun',
			      7	=>	'Jul',
			      8	=>	'Aug',
			      9	=>	'Sep',
			      10	=>	'Oct',
			      11	=>	'Nov',
			      12	=>	'Dec',
			      );
  }



/*
 * convertEntry - convert any laTeX code and convert to UTF-8 ready 
 for storing in the database
 * 
 * @author Mark Grimshaw, modified by Christophe Ambroise 26/10/2003 
 * @param array $entry - a bibtex entry
 * @return $entry converted to utf8
  
 */
function convertEntry($entry)
{
  $this->config = new BIBTEXCONFIG();
  $this->config->bibtex();

  // Construction of the transformation filter 
  foreach($this->config->bibtexSpCh as $key => $value)
    {
      $replaceBibtex[] = chr($key);
      $matchBibtex[] = preg_quote("/$value/");
    }
  foreach($this->config->bibtexSpChOld as $key => $value)
    {
      $replaceBibtex[] = chr($key);
      $matchBibtex[] = preg_quote("/$value/");
    }
  foreach($this->config->bibtexSpChOld2 as $key => $value)
    {
      $replaceBibtex[] = chr($key);
      $matchBibtex[] = preg_quote("/$value/");
    }
  foreach($this->config->bibtexSpChLatex as $key => $value)
    {
      $replaceBibtex[] =  chr($key);
      $matchBibtex[] = preg_quote("/$value/");
    }

  // Processing of the entry
  foreach($entry as $key => $value){
   // The transformation filter  has returned  latin1 code
   // We thus need to work with latin1.
   $value= $this->utf8->smartUtf8_decode($value); 
   $entry[$key] = utf8_encode(preg_replace($matchBibtex, $replaceBibtex, $value));
  }
  return $entry;
}




}


/*****
 * BIBTEXCONFIG: BibTeX Configuration class
 *****/
class BIBTEXCONFIG
{
  // Constructor
  function BIBTEXCONFIG()
  {
  }
  // BibTeX arrays
  function bibtex()
  {
    $this->bibtexSpCh = array(
			      // Deal with '{' and '}' first!
			      0x007B	=>	"\\textbraceleft",
			      0x007D	=>	"\\textbraceright",
			      0x0022	=>	"{\"}",
			      0x0023	=>	"{\#}",
			      0x0025	=>	"{\%}",
			      0x0026	=>	"{\&}",
			      0x003C	=>	"\\textless",
			      0x003E	=>	"\\textgreater",
			      0x005F	=>	"{\_}",
			      0x00A3	=>	"\\textsterling",
			      0x00C0	=>	"{\`A}",
			      0x00C1	=>	"{\'A}",
			      0x00C2	=>	"{\^A}",
			      0x00C3	=>	"{\~A}",
			      0x00C4	=>	'{\"A}',
			      0x00C5	=>	"{\AA}",
			      0x00C6	=>	"{\AE}",
			      0x00C7	=>	"{\c{C}}",
			      0x00C8	=>	"{\`E}",
			      0x00C9	=>	"{\'E}",
			      0x00CA	=>	"{\^E}",
			      0x00CB	=>	'{\"E}',
			      0x00CC	=>	"{\`I}",
			      0x00CD	=>	"{\'I}",
			      0x00CE	=>	"{\^I}",
			      0x00CF	=>	'{\"I}',
			      0x00D1	=>	"{\~N}",
			      0x00D2	=>	"{\`O}",
			      0x00D3	=>	"{\'O}",
			      0x00D4	=>	"{\^O}",
			      0x00D5	=>	"{\~O}",
			      0x00D6	=>	'{\"O}',
			      0x00D8	=>	"{\O}",
			      0x00D9	=>	"{\`U}",
			      0x00DA	=>	"{\'U}",
			      0x00DB	=>	"{\^U}",
			      0x00DC	=>	'{\"U}',
			      0x00DD	=>	"{\'Y}",
			      0x00DF	=>	"{\ss}",
			      0x00E0	=>	"{\`a}",
			      0x00E1	=>	"{\'a}",
			      0x00E2	=>	"{\^a}",
			      0x00E3	=>	"{\~a}",
			      0x00E4	=>	'{\"a}',
			      0x00E5	=>	"{\aa}",
			      0x00E6	=>	"{\ae}",
			      0x00E7	=>	"{\c{c}}",
			      0x00E8	=>	"{\`e}",
			      0x00E9	=>	"{\'e}",
			      0x00EA	=>	"{\^e}",
			      0x00EB	=>	'{\"e}',
			      0x00EC	=>	"{\`\i}",
			      0x00ED	=>	"{\'\i}",
			      0x00EE	=>	"{\^\i}",
			      0x00EF	=>	'{\"\i}',
			      0x00F1	=>	"{\~n}",
			      0x00F2	=>	"{\`o}",
			      0x00F3	=>	"{\'o}",
			      0x00F4	=>	"{\^o}",
			      0x00F5	=>	"{\~o}",
			      0x00F6	=>	'{\"o}',
			      0x00F8	=>	"{\o}",
			      0x00F9	=>	"{\`u}",
			      0x00FA	=>	"{\'u}",
			      0x00FB	=>	"{\^u}",
			      0x00FC	=>	'{\"u}',
			      0x00FD	=>	"{\'y}",
			      0x00FF	=>	'{\"y}',
			      0x00A1	=>	"{\!}",
			      0x00BF	=>	"{\?}",
			      );
    //Old style with extra {} - usually array_flipped
    $this->bibtexSpChOld = array(
				 0x00C0	=>	"{\`{A}}",
				 0x00C1	=>	"{\'{A}}",
				 0x00C2	=>	"{\^{A}}",
				 0x00C3	=>	"{\~{A}}",
				 0x00C4	=>	'{\"{A}}',
				 0x00C5	=>	"{\A{A}}",
				 0x00C6	=>	"{\A{E}}",
				 0x00C7	=>	"{\c{C}}",
				 0x00C8	=>	"{\`{E}}",
				 0x00C9	=>	"{\'{E}}",
				 0x00CA	=>	"{\^{E}}",
				 0x00CB	=>	'{\"{E}}',
				 0x00CC	=>	"{\`{I}}",
				 0x00CD	=>	"{\'{I}}",
				 0x00CE	=>	"{\^{I}}",
				 0x00CF	=>	'{\"{I}}',
				 0x00D1	=>	"{\~{N}}",
				 0x00D2	=>	"{\`{O}}",
				 0x00D3	=>	"{\'{O}}",
				 0x00D4	=>	"{\^{O}}",
				 0x00D5	=>	"{\~{O}}",
				 0x00D6	=>	'{\"{O}}',
				 0x00D8	=>	"{\{O}}",
				 0x00D9	=>	"{\`{U}}",
				 0x00DA	=>	"{\'{U}}",
				 0x00DB	=>	"{\^{U}}",
				 0x00DC	=>	'{\"{U}}',
				 0x00DD	=>	"{\'{Y}}",
				 0x00DF	=>	"{\s{s}}",
				 0x00E0	=>	"{\`{a}}",
				 0x00E1	=>	"{\'{a}}",
				 0x00E2	=>	"{\^{a}}",
				 0x00E3	=>	"{\~{a}}",
				 0x00E4	=>	'{\"{a}}',
				 0x00E5	=>	"{\a{a}}",
				 0x00E6	=>	"{\a{e}}",
				 0x00E7	=>	"{\c{c}}",
				 0x00E8	=>	"{\`{e}}",
				 0x00E9	=>	"{\'{e}}",
				 0x00EA	=>	"{\^{e}}",
				 0x00EB	=>	'{\"{e}}',
				 0x00EC	=>	"{\`\i}",
				 0x00ED	=>	"{\'\i}",
				 0x00EE	=>	"{\^\i}",
				 0x00EF	=>	'{\"\i}',
				 0x00F1	=>	"{\~{n}}",
				 0x00F2	=>	"{\`{o}}",
				 0x00F3	=>	"{\'{o}}",
				 0x00F4	=>	"{\^{o}}",
				 0x00F5	=>	"{\~{o}}",
				 0x00F6	=>	'{\"{o}}',
				 0x00F8	=>	"{\{o}}",
				 0x00F9	=>	"{\`{u}}",
				 0x00FA	=>	"{\'{u}}",
				 0x00FB	=>	"{\^{u}}",
				 0x00FC	=>	'{\"{u}}',
				 0x00FD	=>	"{\'{y}}",
				 0x00FF	=>	'{\"{y}}',
				 0x00A1	=>	"{\{!}}",
				 0x00BF	=>	"{\{?}}",
				 );
    // And there's more?!?!?!?!? (This is not strict bibtex.....)
    $this->bibtexSpChOld2 = array(
				  0x00C0	=>	"\`{A}",
				  0x00C1	=>	"\'{A}",
				  0x00C2	=>	"\^{A}",
				  0x00C3	=>	"\~{A}",
				  0x00C4	=>	'\"{A}',
				  0x00C5	=>	"\A{A}",
				  0x00C6	=>	"\A{E}",
				  0x00C7	=>	"\c{C}",
				  0x00C8	=>	"\`{E}",
				  0x00C9	=>	"\'{E}",
				  0x00CA	=>	"\^{E}",
				  0x00CB	=>	'\"{E}',
				  0x00CC	=>	"\`{I}",
				  0x00CD	=>	"\'{I}",
				  0x00CE	=>	"\^{I}",
				  0x00CF	=>	'\"{I}',
				  0x00D1	=>	"\~{N}",
				  0x00D2	=>	"\`{O}",
				  0x00D3	=>	"\'{O}",
				  0x00D4	=>	"\^{O}",
				  0x00D5	=>	"\~{O}",
				  0x00D6	=>	'\"{O}',
				  0x00D8	=>	"\{O}",
				  0x00D9	=>	"\`{U}",
				  0x00DA	=>	"\'{U}",
				  0x00DB	=>	"\^{U}",
				  0x00DC	=>	'\"{U}',
				  0x00DD	=>	"\'{Y}",
				  0x00DF	=>	"\s{s}",
				  0x00E0	=>	"\`{a}",
				  0x00E1	=>	"\'{a}",
				  0x00E2	=>	"\^{a}",
				  0x00E3	=>	"\~{a}",
				  0x00E4	=>	'\"{a}',
				  0x00E5	=>	"\a{a}",
				  0x00E6	=>	"\a{e}",
				  0x00E7	=>	"\c{c}",
				  0x00E8	=>	"\`{e}",
				  0x00E9	=>	"\'{e}",
				  0x00EA	=>	"\^{e}",
				  0x00EB	=>	'\"{e}',
				  0x00EC	=>	"\`{i}",
				  0x00ED	=>	"\'{i}",
				  0x00EE	=>	"\^{i}",
				  0x00EF	=>	'\"{i}',
				  0x00F1	=>	"\~{n}",
				  0x00F2	=>	"\`{o}",
				  0x00F3	=>	"\'{o}",
				  0x00F4	=>	"\^{o}",
				  0x00F5	=>	"\~{o}",
				  0x00F6	=>	'\"{o}',
				  0x00F8	=>	"\{o}",
				  0x00F9	=>	"\`{u}",
				  0x00FA	=>	"\'{u}",
				  0x00FB	=>	"\^{u}",
				  0x00FC	=>	'\"{u}',
				  0x00FD	=>	"\'{y}",
				  0x00FF	=>	'\"{y}',
				  0x00A1	=>	"\{!}",
				  0x00BF	=>	"\{?}",
				  );
    // Latex code that some bibtex users may be using
    $this->bibtexSpChLatex = array(
				   0x00C0	=>	"\`A",
				   0x00C1	=>	"\'A",
				   0x00C2	=>	"\^A",
				   0x00C3	=>	"\~A",
				   0x00C4	=>	'\"A',
				   0x00C5	=>	"\AA",
				   0x00C6	=>	"\AE",
				   0x00C7	=>	"\cC",
				   0x00C8	=>	"\`E",
				   0x00C9	=>	"\'E",
				   0x00CA	=>	"\^E",
				   0x00CB	=>	'\"E',
				   0x00CC	=>	"\`I",
				   0x00CD	=>	"\'I",
				   0x00CE	=>	"\^I",
				   0x00CF	=>	'\"I',
				   0x00D1	=>	"\~N",
				   0x00D2	=>	"\`O",
				   0x00D3	=>	"\'O",
				   0x00D4	=>	"\^O",
				   0x00D5	=>	"\~O",
				   0x00D6	=>	'\"O',
				   0x00D8	=>	"\O",
				   0x00D9	=>	"\`U",
				   0x00DA	=>	"\'U",
				   0x00DB	=>	"\^U",
				   0x00DC	=>	'\"U',
				   0x00DD	=>	"\'Y",
				   0x00DF	=>	"\ss",
				   0x00E0	=>	"\`a",
				   0x00E1	=>	"\'a",
				   0x00E2	=>	"\^a",
				   0x00E3	=>	"\~a",
				   0x00E4	=>	'\"a',
				   0x00E5	=>	"\aa",
				   0x00E6	=>	"\ae",
				   0x00E7	=>	"\cc",
				   0x00E8	=>	"\`e",
				   0x00E9       =>	"\'e",
				   0x00EA	=>	"\^e",
				   0x00EB	=>	'\"e',
				   0x00EC	=>	"\`i",
				   0x00ED	=>	"\'i",
				   0x00EE	=>	"\^i",
				   0x00EF	=>	'\"i',
				   0x00F1	=>	"\~n",
				   0x00F2	=>	"\`o",
				   0x00F3	=>	"\'o",
				   0x00F4	=>	"\^o",
				   0x00F5	=>	"\~o",
				   0x00F6	=>	'\"o',
				   0x00F8	=>	"\o",
				   0x00F9	=>	"\`u",
				   0x00FA	=>	"\'u",
				   0x00FB	=>	"\^u",
				   0x00FC	=>	'\"u',
				   0x00FD	=>	"\'y",
				   0x00FF	=>	'\"y',
				   0x00A1	=>	"\!",
				   0x00BF	=>	"\?",
				   );
    $this->bibtexSpChPlain = array(
				   0x00C0	=>	"A",
				   0x00C1	=>	"A",
				   0x00C2	=>	"A",
				   0x00C3	=>	"A",
				   0x00C4	=>	'A',
				   0x00C5	=>	"A",
				   0x00C6	=>	"AE",
				   0x00C7	=>	"C",
				   0x00C8	=>	"E",
				   0x00C9	=>	"E",
				   0x00CA	=>	"E",
				   0x00CB	=>	'E',
				   0x00CC	=>	"I",
				   0x00CD	=>	"I",
				   0x00CE	=>	"I",
				   0x00CF	=>	'I',
				   0x00D1	=>	"N",
				   0x00D2	=>	"O",
				   0x00D3	=>	"O",
				   0x00D4	=>	"O",
				   0x00D5	=>	"O",
				   0x00D6	=>	'O',
				   0x00D8	=>	"O",
				   0x00D9	=>	"U",
				   0x00DA	=>	"U",
				   0x00DB	=>	"U",
				   0x00DC	=>	'U',
				   0x00DD	=>	"Y",
				   0x00DF	=>	"ss",
				   0x00E0	=>	"a",
				   0x00E1	=>	"a",
				   0x00E2	=>	"a",
				   0x00E3	=>	"a",
				   0x00E4	=>	'a',
				   0x00E5	=>	"aa",
				   0x00E6	=>	"ae",
				   0x00E7	=>	"c",
				   0x00E8	=>	"e",
				   0x00E9	=>	"e",
				   0x00EA	=>	"e",
				   0x00EB	=>	'e',
				   0x00EC	=>	"i",
				   0x00ED	=>	"i",
				   0x00EE	=>	"i",
				   0x00EF	=>	'i',
				   0x00F1	=>	"n",
				   0x00F2	=>	"o",
				   0x00F3	=>	"o",
				   0x00F4	=>	"o",
				   0x00F5	=>	"o",
				   0x00F6	=>	'o',
				   0x00F8	=>	"o",
				   0x00F9	=>	"u",
				   0x00FA	=>	"u",
				   0x00FB	=>	"u",
				   0x00FC	=>	'u',
				   0x00FD	=>	"u",
				   0x00FF	=>	'u',
				   0x00A1	=>	"u",
				   0x00BF	=>	"u",
				   );
  }
}


?>
