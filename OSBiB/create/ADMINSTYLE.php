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
*	ADMINSTYLE class.
*
*	Administration of citation bibliographic styles
*
*	$Header: /cvsroot/bibliophile/OSBib/create/ADMINSTYLE.php,v 1.3 2005/06/27 22:18:54 sirfragalot Exp $
*****/
class ADMINSTYLE
{
// Constructor
	function ADMINSTYLE($vars)
	{
		$this->vars = $vars;
/**
* THE OSBIB Version number
*/
		$this->osbibVersion = "2.0";
		include_once("SESSION.php");
		$this->session = new SESSION();
		include_once("MESSAGES.php");
		$this->messages = new MESSAGES();
		include_once("SUCCESS.php");
		$this->success = new SUCCESS();
		include_once("ERRORS.php");
		$this->errors = new ERRORS();
		include_once("MISC.php");
		include_once("FORM.php");
		include_once("../LOADSTYLE.php");
		$this->style = new LOADSTYLE();
		$this->styles = $this->style->loadDir(OSBIB_STYLE_DIR);
	}
// check we really are admin
	function gateKeep($method)
	{
// else, run $method
		return $this->$method();
	}
// display options for styles
	function display($message = FALSE)
	{
// Clear previous style in session
		$this->session->clearArray("cite");
		$this->session->clearArray("style");
		$pString = MISC::h($this->messages->text("heading", "styles"), FALSE, 3);
		if($message)
			$pString .= MISC::p($message);
		$pString .= MISC::p(MISC::a("link", $this->messages->text("style", "addLabel"), 
			"index.php?action=adminStyleAddInit"));
		if(sizeof($this->styles))
		{
			$pString .= MISC::p(MISC::a("link", $this->messages->text("style", "copyLabel"), 
				"index.php?action=adminStyleCopyInit"));
			$pString .= MISC::p(MISC::a("link", $this->messages->text("style", "editLabel"), 
				"index.php?action=adminStyleEditInit"));
		}
		return $pString;
	}
// Add a style - display options.
	function addInit($error = FALSE)
	{
		$pString = MISC::h($this->messages->text("heading", "styles", 
			" (" . $this->messages->text("style", "addLabel") . ")"), FALSE, 3);
		if($error)
			$pString .= MISC::p($error, "error", "center");
		$pString .= $this->displayStyleForm('add');
		return $pString;
	}
// Write style to text file
	function add()
	{
		if($error = $this->validateInput('add'))
			$this->badInput($error, 'addInit');
		$this->writeFile();
		$pString = $this->success->text("style", " " . $this->messages->text("misc", "added") . " ");
		$this->styles = $this->style->loadDir(OSBIB_STYLE_DIR);
		return $this->display($pString);
	}
// display styles for editing
	function editInit($error = FALSE)
	{
		$pString = MISC::h($this->messages->text("heading", "styles", 
			" (" . $this->messages->text("style", "editLabel") . ")"), FALSE, 3);
		$pString .= FORM::formHeader("adminStyleEditDisplay");
		$styleFile = $this->session->getVar('editStyleFile');
		if($styleFile)
			$pString .= FORM::selectedBoxValue(FALSE, "editStyleFile", $this->styles, $styleFile, 20);
		else
			$pString .= FORM::selectFBoxValue(FALSE, "editStyleFile", $this->styles, 20);
		$pString .= MISC::br() . FORM::formSubmit('Edit');
		$pString .= FORM::formEnd();
		return $pString;
	}
// Display a style for editing.
	function editDisplay($error = FALSE)
	{
		if(!$error)
			$this->loadEditSession();
		$pString = MISC::h($this->messages->text("heading", "styles", 
			" (" . $this->messages->text("style", "editLabel") . ")"), FALSE, 3);
		if($error)
			$pString .= MISC::p($error, "error", "center");
		$pString .= $this->displayStyleForm('edit');
		return $pString;
	}
// Read data from style file and load it into the session
	function loadEditSession($copy = FALSE)
	{
// Clear previous style in session
		$this->session->clearArray("style");
		include_once("../PARSEXML.php");
		$parseXML = new PARSEXML();
		include_once("../STYLEMAP.php");
		$styleMap = new STYLEMAP();
		$resourceTypes = array_keys($styleMap->types);
		$this->session->setVar('editStyleFile', $this->vars['editStyleFile']);
		$dir = strtolower($this->vars['editStyleFile']);
		$fileName = $this->vars['editStyleFile'] . ".xml";
		if($fh = fopen(OSBIB_STYLE_DIR . "/" . $dir . "/" . $fileName, "r"))
		{
			list($info, $citation, $common, $types) = $parseXML->extractEntries($fh);
			if(!$copy)
			{
				$this->session->setVar("style_shortName", $this->vars['editStyleFile']);
				$this->session->setVar("style_longName", base64_encode($info['description']));
			}
			foreach($citation as $array)
			{
				if(array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array))
					$this->session->setVar("cite_" . $array['_NAME'], 
					base64_encode($array['_DATA']));
			}
			foreach($common as $array)
			{
				if(array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array))
					$this->session->setVar("style_" . $array['_NAME'], 
					base64_encode($array['_DATA']));
			}
			$this->arrayToTemplate($types);
//print_r($types);
//			$this->session->setVar("style_generic", base64_encode($this->generic));
			foreach($resourceTypes as $type)
			{
				$sessionKey = 'style_' . $type;
				if(!empty($this->$type))
					$this->session->setVar($sessionKey, base64_encode($this->$type));
				if(array_key_exists($type, $this->fallback))
				{
					$sessionKey .= "_generic";
					$this->session->setVar($sessionKey, base64_encode($this->fallback[$type]));
				}
			}
		}
		else
			$this->badInput($this->errors->text("file", "read"));
	}
// Transform XML nodal array to resource type template strings
	function arrayToTemplate($types)
	{
		$this->fallback = array();
		foreach($types as $resourceArray)
		{
//print_r($resourceArray); print "<P>";
			$temp = $tempArray = $newArray = $independent = array();
			$empty = FALSE;
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
					if($array['_NAME'] == 'ultimate')
					{
						$temp['ultimate'] = $array['_DATA'];
						continue;
					}
					if(empty($array['_ELEMENTS']))
					{
						$this->fallback[$type] = $array['_DATA'];
						$empty = TRUE;
					}
					foreach($array['_ELEMENTS'] as $elements)
					{
						if($array['_NAME'] == 'independent')
						{
							$split = split("_", $elements['_NAME']);
							$temp[$array['_NAME']][$split[1]] 
							= $elements['_DATA'];
						}
						else
							$temp[$array['_NAME']][$elements['_NAME']] 
							= $elements['_DATA'];
					}
				}
			}
			if($empty)
			{
				$this->$type = array();
				continue;
			}
/**
* Now parse the temp array into template strings
*/
			foreach($temp as $key => $value)
			{
				if(!is_array($value))
				{
					if($key == 'ultimate')
						$ultimate = $value;
					continue;
				}
				if(($key == 'independent'))
				{
					$independent = $value;
					continue;
				}
				$pre = $post = $dependentPre = $dependentPost = $dependentPreAlternative = 
					$dependentPostAlternative = $singular = $plural = $string = FALSE;
				if(array_key_exists('pre', $value))
					$string .= $value['pre'];
				$string .= $key;
				if(array_key_exists('post', $value))
					$string .= $value['post'];
				if(array_key_exists('dependentPre', $value))
				{
					$replace = "%" . $value['dependentPre'] . "%";
					if(array_key_exists('dependentPreAlternative', $value))
						$replace .= $value['dependentPreAlternative'] . "%";
					$string = str_replace("__DEPENDENT_ON_PREVIOUS_FIELD__", $replace, $string);
				}
				if(array_key_exists('dependentPost', $value))
				{
					$replace = "%" . $value['dependentPost'] . "%";
					if(array_key_exists('dependentPostAlternative', $value))
						$replace .= $value['dependentPostAlternative'] . "%";
					$string = str_replace("__DEPENDENT_ON_NEXT_FIELD__", $replace, $string);
				}
				if(array_key_exists('singular', $value) && array_key_exists('plural', $value))
				{
					$replace = "^" . $value['singular'] . "^" . $value['plural'] . "^";
					$string = str_replace("__SINGULAR_PLURAL__", $replace, $string);
				}
				$tempArray[] = $string;
			}
			if(!empty($independent))
			{
				$firstOfPair = FALSE;
				foreach($tempArray as $index => $value)
				{
					if(!$firstOfPair)
					{
						if(array_key_exists($index, $independent))
						{
							$newArray[] = $independent[$index] . '|' . $value;
							$firstOfPair = TRUE;
							continue;
						}
					}
					else
					{
						if(array_key_exists($index, $independent))
						{
							$newArray[] = $value . '|' . $independent[$index];
							$firstOfPair = FALSE;
							continue;
						}
					}
					$newArray[] = $value;
				}
			}
			else
				$newArray = $tempArray;
			$tempString = join('|', $newArray);
			if(isset($ultimate) && (substr($tempString, -1, 1) != $ultimate))
				$tempString .= '|' . $ultimate;
			$this->$type = $tempString;
		}
	}
// Edit groups
	function edit()
	{
		if($error = $this->validateInput('edit'))
			$this->badInput($error, 'editDisplay');
		$dirName = OSBIB_STYLE_DIR . "/" . strtolower(trim($this->vars['styleShortName']));
		$fileName = $dirName . "/" . strtoupper(trim($this->vars['styleShortName'])) . ".xml";
		$this->writeFile($fileName);
		$pString = $this->success->text("style", " " . $this->messages->text("misc", "edited") . " ");
		return $this->display($pString);
	}
// display groups for copying and making a new style
	function copyInit($error = FALSE)
	{
		$pString = MISC::h($this->messages->text("heading", "styles", 
			" (" . $this->messages->text("style", "copyLabel") . ")"), FALSE, 3);
		$pString .= FORM::formHeader("adminStyleCopyDisplay");
		$pString .= FORM::selectFBoxValue(FALSE, "editStyleFile", $this->styles, 20);
		$pString .= MISC::br() . FORM::formSubmit('Edit');
		$pString .= FORM::formEnd();
		return $pString;
	}
// Display a style for copying.
	function copyDisplay($error = FALSE)
	{
		if(!$error)
			$this->loadEditSession(TRUE);
		$pString = MISC::h($this->messages->text("heading", "styles", 
			" (" . $this->messages->text("style", "copyLabel") . ")"), FALSE, 3);
		if($error)
			$pString .= MISC::p($error, "error", "center");
		$pString .= $this->displayStyleForm('copy');
		return $pString;
	}
// display the citation templating form
	function displayCiteForm($type)
	{
		include_once("TABLE.php");
		include_once("../STYLEMAP.php");
		$this->map = new STYLEMAP();
		$pString = MISC::h($this->messages->text("cite", "citationFormat") . " (" . 
			$this->messages->text("cite", "citationFormatInText") . ")");
// 1st., creator style
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
		$exampleName = array("Joe Bloggs", "Bloggs, Joe", "Bloggs Joe", 
			$this->messages->text("cite", "lastName"));
		$exampleInitials = array("T. U. ", "T.U.", "T U ", "TU");
		$example = array($this->messages->text("style", "creatorFirstNameFull"), 
			$this->messages->text("style", "creatorFirstNameInitials"));
		$firstStyle = base64_decode($this->session->getVar("cite_creatorStyle"));
		$otherStyle = base64_decode($this->session->getVar("cite_creatorOtherStyle"));
		$initials = base64_decode($this->session->getVar("cite_creatorInitials"));
		$firstName = base64_decode($this->session->getVar("cite_creatorFirstName"));
		$useInitials = base64_decode($this->session->getVar("cite_useInitials"));
		$td = MISC::b($this->messages->text("cite", "creatorStyle")) . MISC::br() . 
			FORM::selectedBoxValue($this->messages->text("style", "creatorFirstStyle"), 
			"cite_creatorStyle", $exampleName, $firstStyle, 4);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= FORM::selectedBoxValue($this->messages->text("style", "creatorOthers"), 
			"cite_creatorOtherStyle", $exampleName, $otherStyle, 4);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= $this->messages->text("cite", "useInitials") . ' ' . FORM::checkbox(FALSE, 
			"cite_useInitials", $useInitials);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= FORM::selectedBoxValue($this->messages->text("style", "creatorInitials"), 
			"cite_creatorInitials", $exampleInitials, $initials, 4);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= FORM::selectedBoxValue($this->messages->text("style", "creatorFirstName"),
			"cite_creatorFirstName", $example, $firstName, 2);
		$uppercase = base64_decode($this->session->getVar("cite_creatorUppercase")) ? 
			TRUE : FALSE;
		$td .= MISC::P(FORM::checkbox($this->messages->text("style", "uppercaseCreator"), 
			"cite_creatorUppercase", $uppercase));
		$pString .= TABLE::td($td);
// Delimiters
		$twoCreatorsSep = base64_decode($this->session->getVar("cite_twoCreatorsSep"));
		$betweenFirst = base64_decode($this->session->getVar("cite_creatorSepFirstBetween"));
		$betweenNext = base64_decode($this->session->getVar("cite_creatorSepNextBetween"));
		$last = base64_decode($this->session->getVar("cite_creatorSepNextLast"));
		$td = MISC::b($this->messages->text("cite", "creatorSep")) . 
			MISC::p($this->messages->text("style", "ifOnlyTwoCreators") . "&nbsp;" . 
			FORM::textInput(FALSE, "cite_twoCreatorsSep", $twoCreatorsSep, 7, 255)) . 
			$this->messages->text("style", "sepCreatorsFirst") . "&nbsp;" . 
			FORM::textInput(FALSE, "cite_creatorSepFirstBetween", 
				$betweenFirst, 7, 255) . MISC::br() . 
			MISC::p($this->messages->text("style", "sepCreatorsNext") . MISC::br() . 
			$this->messages->text("style", "creatorSepBetween") . "&nbsp;" . 
			FORM::textInput(FALSE, "cite_creatorSepNextBetween", $betweenNext, 7, 255) . 
			$this->messages->text("style", "creatorSepLast") . "&nbsp;" . 
			FORM::textInput(FALSE, "cite_creatorSepNextLast", $last, 7, 255));
		$td .= MISC::br() . "&nbsp;" . MISC::br();
// List abbreviation
		$example = array($this->messages->text("style", "creatorListFull"), 
			$this->messages->text("style", "creatorListLimit"));
		$list = base64_decode($this->session->getVar("cite_creatorList"));
		$listMore = base64_decode($this->session->getVar("cite_creatorListMore"));
		$listLimit = base64_decode($this->session->getVar("cite_creatorListLimit"));
		$listAbbreviation = base64_decode($this->session->getVar("cite_creatorListAbbreviation"));
		$italic = base64_decode($this->session->getVar("cite_creatorListAbbreviationItalic")) ? 
			TRUE : FALSE;
		$td .= MISC::b($this->messages->text("cite", "creatorList")) . 
			MISC::p(FORM::selectedBoxValue(FALSE, 
			"cite_creatorList", $example, $list, 2) . MISC::br() . 
			$this->messages->text("style", "creatorListIf") . ' ' . 
			FORM::textInput(FALSE, "cite_creatorListMore", $listMore, 3) . 
			$this->messages->text("style", "creatorListOrMore") . ' ' . 
			FORM::textInput(FALSE, "cite_creatorListLimit", $listLimit, 3) . MISC::br() . 
			$this->messages->text("style", "creatorListAbbreviation") . ' ' . 
			FORM::textInput(FALSE, "cite_creatorListAbbreviation", $listAbbreviation, 15) . ' ' . 
			FORM::checkbox(FALSE, "cite_creatorListAbbreviationItalic", $italic) . ' ' . 
			$this->messages->text("style", "italics"));
		$list = base64_decode($this->session->getVar("cite_creatorListSubsequent"));
		$listMore = base64_decode($this->session->getVar("cite_creatorListSubsequentMore"));
		$listLimit = base64_decode($this->session->getVar("cite_creatorListSubsequentLimit"));
		$listAbbreviation = base64_decode($this->session->getVar("cite_creatorListSubsequentAbbreviation"));
		$italic = base64_decode($this->session->getVar("cite_creatorListSubsequentAbbreviationItalic")) ? 
			TRUE : FALSE;
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= MISC::b($this->messages->text("cite", "creatorListSubsequent")) . 
			MISC::p(FORM::selectedBoxValue(FALSE, 
			"cite_creatorListSubsequent", $example, $list, 2) . MISC::br() . 
			$this->messages->text("style", "creatorListIf") . ' ' . 
			FORM::textInput(FALSE, "cite_creatorListSubsequentMore", $listMore, 3) . 
			$this->messages->text("style", "creatorListOrMore") . ' ' . 
			FORM::textInput(FALSE, "cite_creatorListSubsequentLimit", $listLimit, 3) . MISC::br() . 
			$this->messages->text("style", "creatorListAbbreviation") . ' ' . 
			FORM::textInput(FALSE, "cite_creatorListSubsequentAbbreviation", $listAbbreviation, 15) . ' ' . 
			FORM::checkbox(FALSE, "cite_creatorListSubsequentAbbreviationItalic", $italic) . ' ' . 
			$this->messages->text("style", "italics"));
		$pString .= TABLE::td($td, FALSE, FALSE, "top");
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
// Miscellaneous citation formatting
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
// Consecutive citations by same author(s)
		$example = array($this->messages->text("cite", "printCreator"), 
			$this->messages->text("cite", "omitCreator"));
		$consecutive = base64_decode($this->session->getVar("cite_consecutiveCreator"));
		$consecutiveSep = base64_decode($this->session->getVar("cite_consecutiveCreatorSep"));
		$td = FORM::selectedBoxValue($this->messages->text("cite", "consecutiveCreator"), 
			"cite_consecutiveCreator", $example, $consecutive, 2);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= $this->messages->text("cite", "consecutiveCreatorSep") . ' ' . 
			FORM::textInput(FALSE, "cite_consecutiveCreatorSep", $consecutiveSep, 7);
		$pString .= TABLE::td($td, FALSE, FALSE, "top");
		$template = base64_decode($this->session->getVar("cite_template"));
		$availableFields = join(', ', $this->map->citation);
		$consecutiveSep = base64_decode($this->session->getVar("cite_consecutiveCitationSep"));
		$year = base64_decode($this->session->getVar("cite_yearFormat"));
		$superscript = base64_decode($this->session->getVar("cite_templateSuperscript"));
		$td = $this->messages->text("cite", "template") . ' ' . 
			FORM::textInput(FALSE, "cite_template", $template, 30, 255) . 
			" " . MISC::span('*', 'required') . MISC::br() . 
			$this->messages->text("cite", "superscript") . ' ' . 
			FORM::checkbox(FALSE, "cite_templateSuperscript", $superscript) . 
			MISC::p(MISC::i($this->messages->text("style", "availableFields")) . 
			MISC::br() . $availableFields, "small");
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= $this->messages->text("cite", "consecutiveCitationSep") . ' ' . 
			FORM::textInput(FALSE, "cite_consecutiveCitationSep", $consecutiveSep, 7);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$pString .= TABLE::td($td, FALSE, FALSE, "top");
		$example = array("132-9", "132-39", "132-139");
		$input = base64_decode($this->session->getVar("cite_pageFormat"));
		$td = FORM::selectedBoxValue($this->messages->text("style", "pageFormat"), 
			"cite_pageFormat", $example, $input, 3);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$example = array("1998", "'98", "98");
		$td .= FORM::selectedBoxValue($this->messages->text("cite", "yearFormat"), 
			"cite_yearFormat", $example, $year, 3);
		$pString .= TABLE::td($td, FALSE, FALSE, "top");
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
// Ambiguous citations
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
		$ambiguousName = base64_decode($this->session->getVar("cite_ambiguousName")) ? TRUE : FALSE;
		$ambiguousMore = base64_decode($this->session->getVar("cite_ambiguousMore")) ? TRUE : FALSE;
		$ambiguousTitle = base64_decode($this->session->getVar("cite_ambiguousTitle")) ? TRUE : FALSE;
		$ambiguousYear = base64_decode($this->session->getVar("cite_ambiguousYear")) ? TRUE : FALSE;
		$nameFormat = base64_decode($this->session->getVar("cite_ambiguousNameFormat"));
		$yearFormat = base64_decode($this->session->getVar("cite_ambiguousYearFormat"));
		$td = MISC::p(MISC::b($this->messages->text("cite", "ambiguous")));
		$td .= MISC::P(FORM::checkbox(FALSE, 
			"cite_ambiguousName", $ambiguousName) . ' ' . $this->messages->text("cite", "ambiguousFull"));
		$td .= MISC::P(FORM::checkbox(FALSE, 
			"cite_ambiguousMore", $ambiguousMore) . ' ' . $this->messages->text("cite", "ambiguousMore"));
		$td .= MISC::P(FORM::checkbox(FALSE, 
			"cite_ambiguousTitle", $ambiguousTitle) . ' ' . $this->messages->text("cite", "ambiguousTitle"));
		$td .= MISC::P(FORM::checkbox(FALSE, 
			"cite_ambiguousYear", $ambiguousYear) . ' ' . $this->messages->text("cite", "ambiguousYear"));
		$pString .= TABLE::td($td, FALSE, FALSE, "top");
		$example = array($this->messages->text("style", "creatorFirstNameFull"), 
			"T. U. ", "T.U.", "T U ", "TU");
		$td = FORM::selectedBoxValue($this->messages->text("cite", "ambiguousNameFormat"), 
			"cite_ambiguousNameFormat", $example, $nameFormat, 5);
		$pString .= TABLE::td($td);
		$example = array("1999a, b", "1999a, 1999b");
		$td = FORM::selectedBoxValue($this->messages->text("cite", "ambiguousYearFormat"), 
			"cite_ambiguousYearFormat", $example, $yearFormat, 2);
		$pString .= TABLE::td($td);
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
// Footnote style citations
		$pString .= MISC::h($this->messages->text("cite", "citationFormat") . " (" . 
			$this->messages->text("cite", "citationFormatFootnote") . ")");
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
		$style = base64_decode($this->session->getVar("cite_footnoteStyle"));
		$example = array($this->messages->text("cite", "footnoteStyleBib"), 
				$this->messages->text("cite", "footnoteStyleInText"));
		$td = FORM::selectedBoxValue(FALSE, "cite_footnoteStyle", $example, $style, 2);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$ibid = base64_decode($this->session->getVar("cite_ibid"));
		$ibidPage = base64_decode($this->session->getVar("cite_ibidPage"));
		$td .= FORM::textInput($this->messages->text("cite", "ibid"), "cite_ibid", $ibid, 30, 255);
		$td .= MISC::br();
		$td .= FORM::checkbox(FALSE, 
			"cite_ibidPage", $ibidPage) . ' ' . $this->messages->text("cite", "ibidPage");
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$idem = base64_decode($this->session->getVar("cite_idem"));
		$td .= FORM::textInput($this->messages->text("cite", "idem"), "cite_idem", $idem, 30, 255);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$opCit = base64_decode($this->session->getVar("cite_opCit"));
		$td .= FORM::textInput($this->messages->text("cite", "opCit"), "cite_opCit", $opCit, 30, 255);
		$pString .= TABLE::td($td);

		$example = array($this->messages->text("cite", "footnoteCitationPageFormatNever"), 
				$this->messages->text("cite", "footnoteCitationPageFormatBib"), 
				$this->messages->text("cite", "footnoteCitationPageFormatTemplate"));
		$pageFormat = base64_decode($this->session->getVar("cite_footnoteCitationPageFormat"));
		$td = FORM::selectedBoxValue($this->messages->text("cite", "footnoteCitationPageFormat"), 
			"cite_footnoteCitationPageFormat", $example, $pageFormat, 3);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$template = base64_decode($this->session->getVar("cite_footnotePageTemplate"));
		$td .= $this->messages->text("cite", "template") . ' ' . 
			FORM::textInput(FALSE, "cite_footnotePageTemplate", $template, 30, 255) . 
			MISC::p(MISC::i($this->messages->text("style", "availableFields")) . 
			MISC::br() . 'pages', "small");
		$td .= MISC::br() . "&nbsp;" . MISC::br();		
		$example = array($this->messages->text("cite", "footnotePageAfter"), 
			$this->messages->text("cite", "footnotePageBefore"));
		$pagePosition = base64_decode($this->session->getVar("cite_footnotePagePosition"));
		$td .= FORM::selectedBoxValue($this->messages->text("cite", "footnotePagePosition"), 
			"cite_footnotePagePosition", $example, $pagePosition, 2);
		
		$pString .= TABLE::td($td);
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		return $pString;
	}
// display the style form for both adding and editing
	function displayStyleForm($type)
	{
		include_once("TABLE.php");
		include_once("../STYLEMAP.php");
		$this->map = new STYLEMAP();
		$types = array_keys($this->map->types);
		if($type == 'add')
			$pString = FORM::formHeader("adminStyleAdd");
		else if($type == 'edit')
			$pString = FORM::formHeader("adminStyleEdit");
		else // copy
			$pString = FORM::formHeader("adminStyleAdd");
		$pString .= TABLE::tableStart();
		$pString .= TABLE::trStart();
		$input = $this->session->getVar("style_shortName");
		if($type == 'add')
			$pString .= TABLE::td(FORM::textInput($this->messages->text("style", "shortName"), 
				"styleShortName", $input, 20, 255) . " " . MISC::span('*', 'required') . 
				MISC::br() . $this->messages->text("hint", "styleShortName"));
		else if($type == 'edit')
			$pString .= FORM::hidden("editStyleFile", $this->vars['editStyleFile']) . 
				FORM::hidden("styleShortName", $input) . 
				TABLE::td(MISC::b($this->vars['editStyleFile'] . ":&nbsp;&nbsp;"), 
				FALSE, FALSE, "top");
		else // copy
			$pString .= TABLE::td(FORM::textInput($this->messages->text("style", "shortName"), 
				"styleShortName", $input, 20, 255) . " " . MISC::span('*', 'required') . 
				MISC::br() . $this->messages->text("hint", "styleShortName"));
		$input = base64_decode($this->session->getVar("style_longName"));
		$pString .= TABLE::td(FORM::textInput($this->messages->text("style", "longName"), 
			"styleLongName", $input, 50, 255) . " " . MISC::span('*', 'required'));
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		$pString .= MISC::p(MISC::hr());
		$pString .= $this->displayCiteForm('copy');
		$pString .= MISC::p(MISC::hr() . MISC::hr());
		$pString .= MISC::h($this->messages->text("style", "bibFormat"));
// Display general options for creator limits, formats etc.
// 1st., creator style
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
		$exampleName = array("Joe Bloggs", "Bloggs, Joe", "Bloggs Joe", 
			$this->messages->text("cite", "lastName"));
		$exampleInitials = array("T. U. ", "T.U.", "T U ", "TU");
		$example = array($this->messages->text("style", "creatorFirstNameFull"), 
			$this->messages->text("style", "creatorFirstNameInitials"));
		$firstStyle = base64_decode($this->session->getVar("style_primaryCreatorFirstStyle"));
		$otherStyle = base64_decode($this->session->getVar("style_primaryCreatorOtherStyle"));
		$initials = base64_decode($this->session->getVar("style_primaryCreatorInitials"));
		$firstName = base64_decode($this->session->getVar("style_primaryCreatorFirstName"));
		$td = MISC::b($this->messages->text("style", "primaryCreatorStyle")) . MISC::br() . 
			FORM::selectedBoxValue($this->messages->text("style", "creatorFirstStyle"), 
			"style_primaryCreatorFirstStyle", $exampleName, $firstStyle, 4);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= FORM::selectedBoxValue($this->messages->text("style", "creatorOthers"), 
			"style_primaryCreatorOtherStyle", $exampleName, $otherStyle, 4);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= FORM::selectedBoxValue($this->messages->text("style", "creatorInitials"), 
			"style_primaryCreatorInitials", $exampleInitials, $initials, 4);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= FORM::selectedBoxValue($this->messages->text("style", "creatorFirstName"),
			"style_primaryCreatorFirstName", $example, $firstName, 2);
		$uppercase = base64_decode($this->session->getVar("style_primaryCreatorUppercase")) ? 
			TRUE : FALSE;
		$td .= MISC::P(FORM::checkbox($this->messages->text("style", "uppercaseCreator"), 
			"style_primaryCreatorUppercase", $uppercase));
		$repeat = base64_decode($this->session->getVar("style_primaryCreatorRepeat"));
		$exampleRepeat = array($this->messages->text("style", "repeatCreators1"), 
			$this->messages->text("style", "repeatCreators2"), 
			$this->messages->text("style", "repeatCreators3"));
		$td .= FORM::selectedBoxValue($this->messages->text("style", "repeatCreators"), 
			"style_primaryCreatorRepeat", $exampleRepeat, $repeat, 3) . MISC::br();
		$repeatString = base64_decode($this->session->getVar("style_primaryCreatorRepeatString"));
		$td .= FORM::textInput(FALSE, "style_primaryCreatorRepeatString", $repeatString, 15, 255);
		$pString .= TABLE::td($td);
		$firstStyle = base64_decode($this->session->getVar("style_otherCreatorFirstStyle"));
		$otherStyle = base64_decode($this->session->getVar("style_otherCreatorOtherStyle"));
		$initials = base64_decode($this->session->getVar("style_otherCreatorInitials"));
		$firstName = base64_decode($this->session->getVar("style_otherCreatorFirstName"));
		$td = MISC::b($this->messages->text("style", "otherCreatorStyle")) . MISC::br() . 
			FORM::selectedBoxValue($this->messages->text("style", "creatorFirstStyle"), 
			"style_otherCreatorFirstStyle", $exampleName, $firstStyle, 4);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= FORM::selectedBoxValue($this->messages->text("style", "creatorOthers"), 
			"style_otherCreatorOtherStyle", $exampleName, $otherStyle, 4);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= FORM::selectedBoxValue($this->messages->text("style", "creatorInitials"), 
			"style_otherCreatorInitials", $exampleInitials, $initials, 4);
		$td .= MISC::br() . "&nbsp;" . MISC::br();
		$td .= FORM::selectedBoxValue($this->messages->text("style", "creatorFirstName"),
			"style_otherCreatorFirstName", $example, $firstName, 2);
		$uppercase = base64_decode($this->session->getVar("style_otherCreatorUppercase")) ? 
			TRUE : FALSE;
		$td .= MISC::P(FORM::checkbox($this->messages->text("style", "uppercaseCreator"), 
			"style_otherCreatorUppercase", $uppercase));
		$pString .= TABLE::td($td);
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
// 2nd., creator delimiters
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
//		$pString .= TABLE::trStart();
//		$pString .= TABLE::tdStart();
//		$pString .= TABLE::tableStart();
		$pString .= TABLE::trStart();
		$twoCreatorsSep = base64_decode($this->session->getVar("style_primaryTwoCreatorsSep"));
		$betweenFirst = base64_decode($this->session->getVar("style_primaryCreatorSepFirstBetween"));
		$betweenNext = base64_decode($this->session->getVar("style_primaryCreatorSepNextBetween"));
		$last = base64_decode($this->session->getVar("style_primaryCreatorSepNextLast"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "primaryCreatorSep")) . 
			MISC::p($this->messages->text("style", "ifOnlyTwoCreators") . "&nbsp;" . 
			FORM::textInput(FALSE, "style_primaryTwoCreatorsSep", $twoCreatorsSep, 7, 255)) . 
			$this->messages->text("style", "sepCreatorsFirst") . "&nbsp;" . 
			FORM::textInput(FALSE, "style_primaryCreatorSepFirstBetween", $betweenFirst, 7, 255) . MISC::br() . 
			MISC::p($this->messages->text("style", "sepCreatorsNext") . MISC::br() . 
			$this->messages->text("style", "creatorSepBetween") . "&nbsp;" . 
			FORM::textInput(FALSE, "style_primaryCreatorSepNextBetween", $betweenNext, 7, 255) . 
			$this->messages->text("style", "creatorSepLast") . "&nbsp;" . 
			FORM::textInput(FALSE, "style_primaryCreatorSepNextLast", $last, 7, 255)), 
			FALSE, FALSE, "bottom");
//		$pString .= TABLE::trEnd();
//		$pString .= TABLE::tableEnd();
//		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
//		$pString .= TABLE::tdEnd();
//		$pString .= TABLE::tdStart();
//		$pString .= TABLE::tableStart();
//		$pString .= TABLE::trStart();
		$twoCreatorsSep = base64_decode($this->session->getVar("style_otherTwoCreatorsSep"));
		$betweenFirst = base64_decode($this->session->getVar("style_otherCreatorSepFirstBetween"));
		$betweenNext = base64_decode($this->session->getVar("style_otherCreatorSepNextBetween"));
		$last = base64_decode($this->session->getVar("style_otherCreatorSepNextLast"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "otherCreatorSep")) . 
			MISC::p($this->messages->text("style", "ifOnlyTwoCreators") . "&nbsp;" . 
			FORM::textInput(FALSE, "style_otherTwoCreatorsSep", $twoCreatorsSep, 7, 255)) . 
			$this->messages->text("style", "sepCreatorsFirst") . "&nbsp;" . 
			FORM::textInput(FALSE, "style_otherCreatorSepFirstBetween", $betweenFirst, 7, 255) .
			MISC::p($this->messages->text("style", "sepCreatorsNext") . MISC::br() . 
			$this->messages->text("style", "creatorSepBetween") . "&nbsp;" . 
			FORM::textInput(FALSE, "style_otherCreatorSepNextBetween", $betweenNext, 7, 255) . 
			$this->messages->text("style", "creatorSepLast") . "&nbsp;" . 
			FORM::textInput(FALSE, "style_otherCreatorSepNextLast", $last, 7, 255)), 
			FALSE, FALSE, "bottom");
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
//		$pString .= TABLE::tdEnd();
// Editor replacements
//		$pString .= TABLE::trEnd();
//		$pString .= TABLE::tableEnd();
//		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
		$switch = base64_decode($this->session->getVar("style_editorSwitch"));
		$editorSwitchIfYes = stripslashes(base64_decode($this->session->getVar("style_editorSwitchIfYes")));
		$example = array($this->messages->text("style", "no"), $this->messages->text("style", "yes"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "editorSwitchHead")) . MISC::br() . 
			FORM::selectedBoxValue($this->messages->text("style", "editorSwitch"), 
			"style_editorSwitch", $example, $switch, 2));
		$pString .= TABLE::td(
			FORM::textInput($this->messages->text("style", "editorSwitchIfYes"), 
			"style_editorSwitchIfYes", $editorSwitchIfYes, 30, 255), FALSE, FALSE, "bottom");
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
// 3rd., creator list limits
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
		$example = array($this->messages->text("style", "creatorListFull"), 
			$this->messages->text("style", "creatorListLimit"));
		$list = base64_decode($this->session->getVar("style_primaryCreatorList"));
		$listMore = base64_decode($this->session->getVar("style_primaryCreatorListMore"));
		$listLimit = base64_decode($this->session->getVar("style_primaryCreatorListLimit"));
		$listAbbreviation = base64_decode($this->session->getVar("style_primaryCreatorListAbbreviation"));
		$italic = base64_decode($this->session->getVar("style_primaryCreatorListAbbreviationItalic")) ? 
			TRUE : FALSE;
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "primaryCreatorList")) . MISC::br() . 
			FORM::selectedBoxValue(FALSE, 
			"style_primaryCreatorList", $example, $list, 2) . MISC::br() . 
			$this->messages->text("style", "creatorListIf") . ' ' . 
			FORM::textInput(FALSE, "style_primaryCreatorListMore", $listMore, 3) . 
			$this->messages->text("style", "creatorListOrMore") . ' ' . 
			FORM::textInput(FALSE, "style_primaryCreatorListLimit", $listLimit, 3) . MISC::br() . 
			$this->messages->text("style", "creatorListAbbreviation") . ' ' . 
			FORM::textInput(FALSE, "style_primaryCreatorListAbbreviation", $listAbbreviation, 15) . ' ' . 
			FORM::checkbox(FALSE, "style_primaryCreatorListAbbreviationItalic", $italic) . ' ' . 
			$this->messages->text("style", "italics"));
		$list = base64_decode($this->session->getVar("style_otherCreatorList"));
		$listMore = base64_decode($this->session->getVar("style_otherCreatorListMore"));
		$listLimit = base64_decode($this->session->getVar("style_otherCreatorListLimit"));
		$listAbbreviation = base64_decode($this->session->getVar("style_otherCreatorListAbbreviation"));
		$italic = base64_decode($this->session->getVar("style_otherCreatorListAbbreviationItalic")) ? 
			TRUE : FALSE;
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "otherCreatorList")) . MISC::br() . 
			FORM::selectedBoxValue(FALSE, 
			"style_otherCreatorList", $example, $list, 2) . MISC::br() . 
			$this->messages->text("style", "creatorListIf") . ' ' . 
			FORM::textInput(FALSE, "style_otherCreatorListMore", $listMore, 3) . 
			$this->messages->text("style", "creatorListOrMore") . ' ' . 
			FORM::textInput(FALSE, "style_otherCreatorListLimit", $listLimit, 3) . MISC::br() . 
			$this->messages->text("style", "creatorListAbbreviation") . ' ' . 
			FORM::textInput(FALSE, "style_otherCreatorListAbbreviation", $listAbbreviation, 15) . ' ' . 
			FORM::checkbox(FALSE, "style_otherCreatorListAbbreviationItalic", $italic) . ' ' . 
			$this->messages->text("style", "italics"));
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
// Title capitalization, edition, day and month, runningTime and page formats
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
		$example = array($this->messages->text("style", "titleAsEntered"), 
			"Wikindx bibliographic management system");
		$input = base64_decode($this->session->getVar("style_titleCapitalization"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "titleCapitalization")) . MISC::br() .
			FORM::selectedBoxValue(FALSE, "style_titleCapitalization", $example, $input, 2));
		$example = array("3", "3rd");
		$input = base64_decode($this->session->getVar("style_editionFormat"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "editionFormat")) . MISC::br() . 
			FORM::selectedBoxValue(FALSE, "style_editionFormat", $example, $input, 2));
		$example = array("132-9", "132-39", "132-139");
		$input = base64_decode($this->session->getVar("style_pageFormat"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "pageFormat")) . MISC::br() . 
			FORM::selectedBoxValue(FALSE, "style_pageFormat", $example, $input, 3));
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
		$example = array("10", "10th");
		$input = base64_decode($this->session->getVar("style_dayFormat"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "dayFormat")) . MISC::br() . 
			FORM::selectedBoxValue(FALSE, "style_dayFormat", $example, $input, 2));
		$example = array("Feb", "February", $this->messages->text("style", "userMonthSelect"));
		$input = base64_decode($this->session->getVar("style_monthFormat"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "monthFormat")) . MISC::br() . 
			FORM::selectedBoxValue(FALSE, "style_monthFormat", $example, $input, 3));
		$example = array("Day Month", "Month Day");
		$input = base64_decode($this->session->getVar("style_dateFormat"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "dateFormat")) . MISC::br() . 
			FORM::selectedBoxValue(FALSE, "style_dateFormat", $example, $input, 2));
		$example = array("3'45\"", "3:45", "3,45", "3 hours, 45 minutes", "3 hours and 45 minutes");
		$input = base64_decode($this->session->getVar("style_runningTimeFormat"));
		$pString .= TABLE::td(MISC::b($this->messages->text("style", "runningTimeFormat")) . MISC::br() . 
			FORM::selectedBoxValue(FALSE, "style_runningTimeFormat", $example, $input, 5));
		$pString .= TABLE::trEnd();
		$pString .= TABLE::trStart();
		$monthString = '';	
		for($i = 1; $i <= 12; $i++)
		{
			$input = base64_decode($this->session->getVar("style_userMonth_$i"));
			if($i == 7)
				$monthString .= MISC::br() . "$i:&nbsp;&nbsp;" . 
				FORM::textInput(FALSE, "style_userMonth_$i", $input, 15, 255);
			else
				$monthString .= "$i:&nbsp;&nbsp;" . 
				FORM::textInput(FALSE, "style_userMonth_$i", $input, 15, 255);
		}
		$pString .= TABLE::td($this->messages->text("style", "userMonths") . MISC::br() . 
			$monthString, FALSE, FALSE, FALSE, 5);
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		$pString .= MISC::br() . "&nbsp;" . MISC::br();
// Date range formatting
		$pString .= TABLE::tableStart("styleTable", 1, FALSE, 5);
		$pString .= TABLE::trStart();
		
		$td = MISC::p(MISC::b($this->messages->text("style", "dateRange")));
		$input = base64_decode($this->session->getVar("style_dateRangeDelimit1"));
		$td .= MISC::p(FORM::textInput($this->messages->text("style", "dateRangeDelimit1"), 
			"style_dateRangeDelimit1", $input, 6, 255));
		$input = base64_decode($this->session->getVar("style_dateRangeDelimit2"));
		$td .= MISC::p(FORM::textInput($this->messages->text("style", "dateRangeDelimit2"), 
			"style_dateRangeDelimit2", $input, 6, 255));
		$input = base64_decode($this->session->getVar("style_dateRangeSameMonth"));
		$example = array($this->messages->text("style", "dateRangeSameMonth1"), 
			$this->messages->text("style", "dateRangeSameMonth2"));
		$td .= MISC::p(FORM::selectedBoxValue($this->messages->text("style", "dateRangeSameMonth"),
			"style_dateRangeSameMonth", $example, $input, 2));
		
		$pString .= TABLE::td($td);
			
		$pString .= TABLE::trEnd();
		$pString .= TABLE::tableEnd();
		$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		$pString .= MISC::br() . MISC::hr() . MISC::br();
		$generic = array("genericBook" => $this->messages->text("resourceType", "genericBook"), 
			"genericArticle" => $this->messages->text("resourceType", "genericArticle"), 
			"genericMisc" => $this->messages->text("resourceType", "genericMisc"));
// Resource types
		foreach($types as $key)
		{
			if(($key == 'genericBook') || ($key == 'genericArticle') || ($key == 'genericMisc'))
			{
				$required = " " . MISC::span('*', 'required');
				$fallback = FALSE;
			}
			else
			{
				$required = FALSE;
				$formElementName = "style_" . $key . "_generic";
				$input = $this->session->issetVar($formElementName) ? 
					base64_decode($this->session->getVar($formElementName)) : "genericMisc";
				$fallback = FORM::selectedBoxValue($this->messages->text("style", "fallback"), 
					$formElementName, $generic, $input, 3);
			}
			$pString .= MISC::br() . MISC::hr() . MISC::br();
			$pString .= TABLE::tableStart();
			$pString .= TABLE::trStart();
			$keyName = 'style_' . $key;
			$input = stripslashes(base64_decode($this->session->getVar($keyName)));
			$pString .= TABLE::td(FORM::textareaInput($this->messages->text("resourceType", $key), 
				$keyName, $input, 80, 3) . $required . MISC::br() . 
			$this->messages->text("hint", "caseSensitive"));
// List available fields for this type
			$availableFields = join(', ', array_values($this->map->$key));
			$pString .= TABLE::td(MISC::p(MISC::i($this->messages->text("style", "availableFields")) . 
				MISC::br() . $availableFields, "small") . MISC::p($fallback) . 
				MISC::p(MISC::a("link linkHidden", "preview", 
				"javascript:openPopUpStylePreview('index.php?action=previewStyle', 
				'100', '750', '$keyName')")));
            $pString .= TABLE::trEnd();
            // iframe preview
/*            
            $pString .= TABLE::trStart();

            $pString .= "<td><textarea name='$keyName' id='$key' ".
                        "onchange=\"document.getElementById('previewIframe').src=".
                        "'index.php?action=previewStyle&template='+this.value.replace(/&/, '!!amp!!').replace(/=/,'!!eq!!');\">".
                        "$input</textarea><iframe id='previewIframe'/></td>";
            $pString .= TABLE::trEnd();
*/
			// end iframe preview
			$pString .= TABLE::tableEnd();
			$pString .= TABLE::tdEnd() . TABLE::trEnd() . TABLE::trStart() . TABLE::tdStart();
		}
		if(($type == 'add') || ($type == 'copy'))
			$pString .= MISC::p(FORM::formSubmit('Add'));
		else
			$pString .= MISC::p(FORM::formSubmit('Edit'));
		$pString .= FORM::formEnd();
		return $pString;
	}
// parse input into array
	function parseStringToArray($type, $subject, $map = FALSE, $preview = FALSE)
	{
		if(!$subject)
			return array();
		if($map)
			$this->map = $map;
		$search = join('|', $this->map->$type);
		$subjectArray = split("\|", $subject);
// Loop each field string
		$index = 0;
		$independentFound = FALSE;
		foreach($subjectArray as $subject)
		{
			$dependentPre = $dependentPost = $dependentPreAlternative = 
				$dependentPostAlternative = $singular = $plural = FALSE;
// First grab fieldNames from the input string.
			preg_match("/(.*)(?<!`|[a-zA-Z])($search)(?!`|[a-zA-Z])(.*)/", $subject, $array);
			if(empty($array))
			{
				if($independentFound)
				{
					$independent['independent_' . ($index - 1)] = $subject;
					$independentFound = FALSE;
				}
				else
				{
					$independent['independent_' . $index] = $subject;
					$independentFound = TRUE;
				}
				continue;
			}
// At this stage, [2] is the fieldName, [1] is what comes before and [3] is what comes after.
			$pre = $array[1];
			$fieldName = $array[2];
			$post = $array[3];
// Anything in $pre enclosed in '%' characters is only to be printed if the resource has something in the 
// previous field -- replace with unique string for later preg_replace().
			if(preg_match("/%(.*)%(.*)%|%(.*)%/U", $pre, $dependent))
			{
// if sizeof == 4, we have simply %*% with the significant character in [3].
// if sizeof == 3, we have %*%*% with dependent in [1] and alternative in [2].
				$pre = str_replace($dependent[0], "__DEPENDENT_ON_PREVIOUS_FIELD__", $pre);
				if(sizeof($dependent) == 4)
				{
					$dependentPre = $dependent[3];
					$dependentPreAlternative = '';
				}
				else
				{
					$dependentPre = $dependent[1];
					$dependentPreAlternative = $dependent[2];
				}
			}
// Anything in $post enclosed in '%' characters is only to be printed if the resource has something in the 
// next field -- replace with unique string for later preg_replace().
			if(preg_match("/%(.*)%(.*)%|%(.*)%/U", $post, $dependent))
			{
				$post = str_replace($dependent[0], "__DEPENDENT_ON_NEXT_FIELD__", $post);
				if(sizeof($dependent) == 4)
				{
					$dependentPost = $dependent[3];
					$dependentPostAlternative = '';
				}
				else
				{
					$dependentPost = $dependent[1];
					$dependentPostAlternative = $dependent[2];
				}
			}
// find singular/plural alternatives in $pre and $post and replace with unique string for later preg_replace().
			if(preg_match("/\^(.*)\^(.*)\^/U", $pre, $matchCarat))
			{
				$pre = str_replace($matchCarat[0], "__SINGULAR_PLURAL__", $pre);
				$singular = $matchCarat[1];
				$plural = $matchCarat[2];
			}
			else if(preg_match("/\^(.*)\^(.*)\^/U", $post, $matchCarat))
			{
				$post = str_replace($matchCarat[0], "__SINGULAR_PLURAL__", $post);
				$singular = $matchCarat[1];
				$plural = $matchCarat[2];
			}
// Now dump into $final[$fieldName] stripping any backticks
			if($dependentPre)
//				$final[$fieldName]['dependentPre'] = str_replace('`', '', $dependentPre);
$final[$fieldName]['dependentPre'] = $dependentPre;
			else
				$final[$fieldName]['dependentPre'] = '';
			if($dependentPost)
//				$final[$fieldName]['dependentPost'] = str_replace('`', '', $dependentPost);
$final[$fieldName]['dependentPost'] = $dependentPost;
			if($dependentPreAlternative)
//				$final[$fieldName]['dependentPreAlternative'] = 
//				str_replace('`', '', $dependentPreAlternative);
$final[$fieldName]['dependentPreAlternative'] = $dependentPreAlternative;
			else
				$final[$fieldName]['dependentPreAlternative'] = '';
			if($dependentPostAlternative)
//				$final[$fieldName]['dependentPostAlternative'] = 
//				str_replace('`', '', $dependentPostAlternative);
$final[$fieldName]['dependentPostAlternative'] = $dependentPostAlternative;
			else
				$final[$fieldName]['dependentPostAlternative'] = '';
			if($singular)
//				$final[$fieldName]['singular'] = str_replace('`', '', $singular);
$final[$fieldName]['singular'] = $singular;
			else
				$final[$fieldName]['singular'] = '';
			if($plural)
//				$final[$fieldName]['plural'] = str_replace('`', '', $plural);
$final[$fieldName]['plural'] = $plural;
			else
				$final[$fieldName]['plural'] = '';
			$final[$fieldName]['pre'] = str_replace('`', '', $pre);
			$final[$fieldName]['post'] = str_replace('`', '', $post);
			$index++;
			$final[$fieldName]['pre'] = $pre;
			$final[$fieldName]['post'] = $post;
		}
		if(!isset($final)) // presumably no field names...
		{
			if($preview)
				return FALSE;
			$this->badInput($this->errors->text("inputError", "invalid"), $this->errorDisplay);
		}
// last element of odd number is actually ultimate punctuation
		if(isset($independent) && sizeof($independent) % 2)
			$final['ultimate'] = array_pop($independent);
		if(isset($independent) && !empty($independent))
			$final['independent'] = $independent;
		return $final;
	}
// write the styles to file.
// If !$fileName, this is called from add() and we create folder/filename immediately before writing to file.
// If $fileName, this comes from edit()
	function writeFile($fileName = FALSE)
	{
		if($fileName)
			$this->errorDisplay = 'editInit';
		else
			$this->errorDisplay = 'addInit';
		include_once("TABLE.php");
		include_once("../STYLEMAP.php");
		$this->map = new STYLEMAP();
		include_once("../UTF8.php");
		$this->utf8 = new UTF8();
		$types = array_keys($this->map->types);
// Start XML
		$fileString = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		$fileString .= "<style xml:lang=\"en\">";
// Main style information
		$fileString .= "<info>";
		$fileString .= "<name>" . trim(stripslashes($this->vars['styleShortName'])) . "</name>";
		$fileString .= "<description>" . htmlspecialchars(trim(stripslashes($this->vars['styleLongName'])))
			 . "</description>";
// Temporary place holder
		$fileString .= "<language>English</language>";
		$fileString .= "<osbibVersion>$this->osbibVersion</osbibVersion>";
		$fileString .= "</info>";
// Start citation definition
		$fileString .= "<citation>";
		$inputArray = array(
			"cite_creatorStyle", "cite_creatorOtherStyle", "cite_creatorInitials", 
			"cite_creatorFirstName", "cite_twoCreatorsSep", "cite_creatorSepFirstBetween", 
			"cite_creatorListSubsequentAbbreviation", "cite_creatorSepNextBetween", 
			"cite_creatorSepNextLast", "cite_creatorList", "cite_creatorListMore", 
			"cite_creatorListLimit", "cite_creatorListAbbreviation", "cite_creatorUppercase", 
			"cite_creatorListSubsequentAbbreviationItalic", "cite_creatorListAbbreviationItalic", 
			"cite_creatorListSubsequent", "cite_creatorListSubsequentMore", 
			"cite_creatorListSubsequentLimit", "cite_consecutiveCreator", "cite_consecutiveCreatorSep", 
			"cite_template", "cite_useInitials", "cite_consecutiveCitationSep", "cite_yearFormat", 
			"cite_pageFormat", "cite_templateSuperscript", "cite_ambiguousName", "cite_ambiguousMore", 
			"cite_ambiguousTitle", "cite_ambiguousYear", "cite_ibid", "cite_idem", "cite_opCit", 
			"cite_ambiguousNameFormat", "cite_ambiguousYearFormat", "cite_footnotePagePosition", 
			"cite_footnotePageTemplate", "cite_ibidPage",  "cite_footnoteStyle",
		);
		foreach($inputArray as $input)
		{
			if(isset($this->vars[$input]))
			{
				$split = split("_", $input, 2);
				$elementName = $split[1];
				$fileString .= "<$elementName>" . 
					htmlspecialchars(stripslashes($this->vars[$input])) . "</$elementName>";
			}
		}
		$fileString .= "</citation>";
// Start bibliography
		$fileString .= "<bibliography>";
// Common section defining how authors, titles etc. are formatted
		$fileString .= "<common>";
		$inputArray = array(
// style
			"style_titleCapitalization", "style_primaryCreatorFirstStyle", 
			"style_primaryCreatorOtherStyle", "style_primaryCreatorInitials", 
			"style_primaryCreatorFirstName", "style_otherCreatorFirstStyle", 
			"style_otherCreatorOtherStyle", "style_otherCreatorInitials", "style_dayFormat", 
			"style_otherCreatorFirstName", "style_primaryCreatorList", "style_otherCreatorList",
			"style_primaryCreatorListAbbreviationItalic", "style_otherCreatorListAbbreviationItalic", 
			"style_monthFormat", "style_editionFormat", "style_primaryCreatorListMore", 
			"style_primaryCreatorListLimit", "style_dateFormat", 
			"style_primaryCreatorListAbbreviation", "style_otherCreatorListMore", 
			"style_runningTimeFormat", "style_primaryCreatorRepeatString", "style_primaryCreatorRepeat", 
			"style_otherCreatorListLimit", "style_otherCreatorListAbbreviation", "style_pageFormat", 
			"style_editorSwitch", "style_editorSwitchIfYes", "style_primaryCreatorUppercase", 
			"style_otherCreatorUppercase", "style_primaryCreatorSepFirstBetween", 
			"style_primaryCreatorSepNextBetween", "style_primaryCreatorSepNextLast", 
			"style_otherCreatorSepFirstBetween", "style_otherCreatorSepNextBetween", 
			"style_otherCreatorSepNextLast", "style_primaryTwoCreatorsSep", "style_otherTwoCreatorsSep", 
			"style_userMonth_1", "style_userMonth_2", "style_userMonth_3", "style_userMonth_4", 
			"style_userMonth_5", "style_userMonth_6", "style_userMonth_7", "style_userMonth_8", 
			"style_userMonth_9", "style_userMonth_10", "style_userMonth_11", "style_userMonth_12", 
			"style_dateRangeDelimit1", "style_dateRangeDelimit2", "style_dateRangeSameMonth", 
		);
		foreach($inputArray as $input)
		{
			if(isset($this->vars[$input]))
			{
				$split = split("_", $input, 2);
				$elementName = $split[1];
				$fileString .= "<$elementName>" . 
					htmlspecialchars(stripslashes($this->vars[$input])) . "</$elementName>";
			}
		}
		$fileString .= "</common>";
// Resource types
		foreach($types as $key)
		{
			$type = 'style_' . $key;
			$input = trim(stripslashes($this->vars[$type]));
// remove newlines etc.
			$input = preg_replace("/\r|\n|\015|\012/", "", $input);
			$fileString .= "<resource name=\"$key\">";
			$fileString .= $this->arrayToXML($this->parseStringToArray($key, $input), $type);
			$fileString .= "</resource>";
		}
		$fileString .= "</bibliography>";
		$fileString .= "</style>";
		if(!$fileName) // called from add()
		{
// Create folder with lowercase styleShortName
			$dirName = OSBIB_STYLE_DIR . "/" . strtolower(trim($this->vars['styleShortName']));
			if(!mkdir($dirName))
				$this->badInput($error = $this->errors->text("file", "folder"), $this->errorDisplay);
			$fileName = $dirName . "/" . strtoupper(trim($this->vars['styleShortName'])) . ".xml";
		}
		if(!$fp = fopen("$fileName", "w"))
			$this->badInput($this->errors->text("file", "write", ": $fileName"), $this->errorDisplay);
		if(!fputs($fp, $this->utf8->encodeUtf8($fileString)))
			$this->badInput($this->errors->text("file", "write", ": $fileName"), $this->errorDisplay);
		fclose($fp);
	}
// Parse array to XML
	function arrayToXML($array, $type)
	{
		$fileString = '';
		if(empty($array)) // no style definition for this type so set fallback
		{
			$name = $type . "_generic";
			if(!isset($this->vars[$name]))
				$name = "genericMisc";
			else
				$name = $this->vars[$name];
			return "<fallbackstyle>$name</fallbackstyle>";
		}
		foreach($array as $key => $value)
		{
			$fileString .= "<$key>";
			if(is_array($value))
				$fileString .= $this->arrayToXML($value, $type);
			else
				$fileString .= htmlspecialchars($value);
			$fileString .= "</$key>";
		}
		return $fileString;
	}
// validate input
	function validateInput($type)
	{
		$error = FALSE;
		if(($type == 'add') || ($type == 'edit'))
		{
			$array = array("style_titleCapitalization", "style_primaryCreatorFirstStyle", 
				"style_primaryCreatorOtherStyle", "style_primaryCreatorInitials", 
				"style_primaryCreatorFirstName", "style_otherCreatorFirstStyle", "style_dateFormat", 
				"style_otherCreatorOtherStyle", "style_otherCreatorInitials", "style_pageFormat", 
				"style_otherCreatorFirstName", "style_primaryCreatorList", "style_dayFormat", 
				"style_otherCreatorList", "style_monthFormat", "style_editionFormat",
				"style_runningTimeFormat", "style_editorSwitch", "style_primaryCreatorRepeat", 
				"style_dateRangeSameMonth", 
		"cite_creatorStyle", "cite_creatorOtherStyle", "cite_creatorInitials", "cite_creatorFirstName", 
		"cite_twoCreatorsSep", "cite_creatorSepFirstBetween", "cite_creatorListSubsequentAbbreviation", 
		"cite_creatorSepNextBetween", "cite_creatorSepNextLast", 
		"cite_creatorList", "cite_creatorListMore", "cite_creatorListLimit", "cite_creatorListAbbreviation",  
		"cite_creatorListSubsequent", "cite_creatorListSubsequentMore", "cite_creatorListSubsequentLimit", 
		"cite_consecutiveCreator", "cite_consecutiveCreatorSep", "cite_template", 
		"cite_consecutiveCitationSep", "cite_yearFormat", "cite_pageFormat", "cite_footnoteStyle",
		);

			$this->writeSession($array);
			if(!trim($this->vars['styleShortName']))
				$error = $this->errors->text("inputError", "missing");
			else
				$this->session->setVar("style_shortName", trim($this->vars['styleShortName']));
			if(preg_match("/\s/", trim($this->vars['styleShortName'])))
				$error = $this->errors->text("inputError", "invalid");
			else if(!trim($this->vars['styleLongName']))
				$error = $this->errors->text("inputError", "missing");
			else if(!trim($this->vars['cite_template']))
				$error = $this->errors->text("inputError", "missing");
			else if(!trim($this->vars['style_genericBook']))
				$error = $this->errors->text("inputError", "missing");
			else if(!trim($this->vars['style_genericArticle']))
				$error = $this->errors->text("inputError", "missing");
			else if(!trim($this->vars['style_genericMisc']))
				$error = $this->errors->text("inputError", "missing");
			foreach($array as $input)
			{
				if(!isset($this->vars[$input]))
					return $this->errors->text("inputError", "missing");
			}
// If xxx_creatorList set to 1 (limit), we must have style_xxxCreatorListMore and xxx_CreatorListLimit. The 
// latter two must be numeric.
			if(($this->vars['style_primaryCreatorList'] == 1) && 
				(!trim($this->vars['style_primaryCreatorListLimit']) || 
				(!$this->vars['style_primaryCreatorListMore'])))
					$error = $this->errors->text("inputError", "missing");
			else if(($this->vars['style_primaryCreatorList'] == 1) && 
				(!is_numeric($this->vars['style_primaryCreatorListLimit']) || 
				!is_numeric($this->vars['style_primaryCreatorListMore'])))
					$error = $this->errors->text("inputError", "nan");
			else if(($this->vars['style_otherCreatorList'] == 1) && 
				(!trim($this->vars['style_otherCreatorListLimit']) || 
				(!$this->vars['style_otherCreatorListMore'])))
					$error = $this->errors->text("inputError", "missing");
			else if(($this->vars['style_otherCreatorList'] == 1) && 
				(!is_numeric($this->vars['style_otherCreatorListLimit']) || 
				!is_numeric($this->vars['style_otherCreatorListMore'])))
					$error = $this->errors->text("inputError", "nan");
			else if(($this->vars['cite_creatorList'] == 1) && 
				(!trim($this->vars['cite_creatorListLimit']) || 
				(!$this->vars['cite_creatorListMore'])))
					$error = $this->errors->text("inputError", "missing");
			else if(($this->vars['cite_creatorList'] == 1) && 
				(!is_numeric($this->vars['cite_creatorListLimit']) || 
				!is_numeric($this->vars['cite_creatorListMore'])))
					$error = $this->errors->text("inputError", "nan");
			else if(($this->vars['cite_creatorListSubsequent'] == 1) && 
				(!trim($this->vars['cite_creatorListSubsequentLimit']) || 
				(!$this->vars['cite_creatorListSubsequentMore'])))
					$error = $this->errors->text("inputError", "missing");
			else if(($this->vars['cite_creatorListSubsequent'] == 1) && 
				(!is_numeric($this->vars['cite_creatorListSubsequentLimit']) || 
				!is_numeric($this->vars['cite_creatorListSubsequentMore'])))
					$error = $this->errors->text("inputError", "nan");
			else if(($this->vars['style_editorSwitch'] == 1) && 
				!trim($this->vars['style_editorSwitchIfYes']))
					$error = $this->errors->text("inputError", "missing");
			else if(($this->vars['style_primaryCreatorRepeat'] == 2) && 
				!trim($this->vars['style_primaryCreatorRepeatString']))
					$error = $this->errors->text("inputError", "missing");
			else if($this->vars['style_monthFormat'] == 2)
			{
				for($i = 1; $i <= 12; $i++)
				{
					if(!trim($this->vars["style_userMonth_$i"]))
						$error = $this->errors->text("inputError", "missing");
				}
			}
		}
		if($type == 'add')
		{
			if(preg_match("/\s/", trim($this->vars['styleShortName'])))
				$error = $this->errors->text("inputError", "invalid");
			else if(array_key_exists(strtoupper(trim($this->vars['styleShortName'])), $this->styles))
				$error = $this->errors->text("inputError", "styleExists");
		}
		else if($type == 'editDisplay')
		{
			if(!array_key_exists('editStyleFile', $this->vars))
				$error = $this->errors->text("inputError", "missing");
		}
		if($error)
			return $error;
// FALSE means validated input
		return FALSE;
	}
// Write session
	function writeSession($array)
	{
		include_once("TABLE.php");
		include_once("../STYLEMAP.php");
		$this->map = new STYLEMAP();
		$types = array_keys($this->map->types);
		if(trim($this->vars['styleLongName']))
			$this->session->setVar("style_longName", base64_encode(trim($this->vars['styleLongName'])));
// other resource types
		foreach($types as $key)
		{
			$type = 'style_' . $key;
			if(trim($this->vars[$type]))
				$this->session->setVar($type, base64_encode(trim($this->vars[$type])));
// Fallback styles
			if(($key != 'genericBook') && ($key != 'genericArticle') && ($key != 'genericMisc'))
			{
				$name = $type . "_generic";
				$this->session->setVar($name, base64_encode(trim($this->vars[$name])));
			}
		}
// Other values. $array parameter is required, other optional input is added to the array
		$array[] = "style_primaryCreatorSepBetween";
		$array[] = "style_primaryCreatorSepLast";
		$array[] = "style_otherCreatorSepBetween";
		$array[] = "style_otherCreatorSepLast";
		$array[] = "style_primaryCreatorListMore";
		$array[] = "style_primaryCreatorListLimit";
		$array[] = "style_primaryCreatorListAbbreviation";
		$array[] = "style_otherCreatorListMore";
		$array[] = "style_otherCreatorListLimit";
		$array[] = "style_otherCreatorListAbbreviation";
		$array[] = "style_editorSwitchIfYes";
		$array[] = "style_primaryCreatorUppercase";
		$array[] = "style_otherCreatorUppercase";
		$array[] = "style_primaryTwoCreatorsSep";
		$array[] = "style_primaryCreatorSepFirstBetween";
		$array[] = "style_primaryCreatorSepNextBetween";
		$array[] = "style_primaryCreatorSepNextLast";
		$array[] = "style_otherTwoCreatorsSep";
		$array[] = "style_otherCreatorSepFirstBetween";
		$array[] = "style_otherCreatorSepNextBetween";
		$array[] = "style_otherCreatorSepNextLast";
		$array[] = "style_primaryCreatorRepeatString";
		$array[] = "style_primaryCreatorListAbbreviationItalic";
		$array[] = "style_otherCreatorListAbbreviationItalic";
		$array[] = "style_userMonth_1";
		$array[] = "style_userMonth_2";
		$array[] = "style_userMonth_3";
		$array[] = "style_userMonth_4";
		$array[] = "style_userMonth_5";
		$array[] = "style_userMonth_6";
		$array[] = "style_userMonth_7";
		$array[] = "style_userMonth_8";
		$array[] = "style_userMonth_9";
		$array[] = "style_userMonth_10";
		$array[] = "style_userMonth_11";
		$array[] = "style_userMonth_12";
		$array[] = "style_dateRangeDelimit1";
		$array[] = "style_dateRangeDelimit2";
		$array[] = "cite_useInitials";
		$array[] = "cite_creatorUppercase";
		$array[] = "cite_creatorListAbbreviationItalic";
		$array[] = "cite_creatorListSubsequentAbbreviationItalic";
		$array[] = "cite_templateSuperscript";
		$array[] = "cite_ambiguousName";
		$array[] = "cite_ambiguousMore";
		$array[] = "cite_ambiguousTitle";
		$array[] = "cite_ambiguousYear";
		$array[] = "cite_ambiguousNameFormat";
		$array[] = "cite_ambiguousYearFormat";
		$array[] = "cite_ibid";
		$array[] = "cite_idem";
		$array[] = "cite_opCit";
		$array[] = "cite_footnotePagePosition";
		$array[] = "cite_footnotePageTemplate";
		$array[] = "cite_ibidPage";
		foreach($array as $input)
		{
			if(isset($this->vars[$input]))
				$this->session->setVar($input, base64_encode($this->vars[$input]));
			else
				$this->session->delVar($input);
		}
	}
// bad Input function
	function badInput($error, $method)
	{
		include_once("CLOSE.php");
		new CLOSE($this->$method($error));
	}
}
?>

