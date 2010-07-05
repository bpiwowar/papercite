<?php
/********************************
OSBib:
A collection of PHP classes to create and manage bibliographic formatting for OS bibliography software 
using the OSBib standard.  Originally developed for WIKINDX (http://wikindx.sourceforge.net)

Released through http://bibliophile.sourceforge.net under the GPL licence.
Do whatever you like with this -- some credit to the author(s) would be appreciated.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net 
so that your improvements can be added to the release package.

Mark Grimshaw 2005
http://bibliophile.sourceforge.net
********************************/

/** Description of class CITEFORMAT
* Format citations.
* 
* @author	Mark Grimshaw
* @version	1
*/
class CITEFORMAT
{
/**
* $bibStyle is the object that handles bibliography formatting of appended bibliographies.
* $bibStyleProcess is the method in $bibStyle that starts the formatting of a bibliographic entry.
* $dir is the path to STYLEMAP.php etc.
* $utfDir is a WIKINDX-specific setting
*/
	function CITEFORMAT(&$bibStyle, $bibStyleProcess, $dir = FALSE, $utf8Dir = FALSE)
	{
		$this->bibStyle = $bibStyle;
		$this->bibStyleProcess = $bibStyleProcess;
//05/05/2005 G.GARDEY: add a last "/" to $dir if not present.
		if(!$dir)
			$this->dir = dirname(__FILE__) . "../";
		else
		{
			$dir = trim($dir);
			$this->dir = $dir;
			if($dir[strlen($dir)-1] != "/")
				$this->dir .= "/";
		}
		include_once($this->dir . "STYLEMAP.php");
		$this->styleMap = new STYLEMAP();
		include_once($this->dir . "UTF8.php");
		$this->utf8 = new UTF8();
		$this->patterns = ''; // not needed here but must be set for EXPORTFILTER
		include_once("EXPORTFILTER.php");
		$this->citationIds = $this->creatorIds = $this->consecutiveCreatorSep = $this->endnoteStringArray = 
			$this->creators = $this->endnoteCitations = $this->endnoteSameIdsArray = $this->inTextDoneIds = 
			$this->endnotes = $this->opCit = $this->rtfDoneIds = $this->intextBibliography = 
			$this->creatorSurnames = array();
		$this->endnoteSameIds = 0;
/**
* Output medium:
* 'html', 'rtf', or 'plain'
*/
		$this->output = 'html'; // default if not set externally
		$this->styleSheet = FALSE; // For RTF
		$this->hyperlinkBase = FALSE; // no hyperlinking of cited resources (i.e. for $this->output other than 'html')
		$this->endnoteString = FALSE;
		$this->matchNameSplit = $this->matchNameSplitEtAl = FALSE; // split page from main citation (in-text only)
// WIKINDX-specific
		$this->wikindx = FALSE;
	}
/**
* Read the chosen bibliographic style and create arrays.
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
			return array(FALSE, FALSE, FALSE, FALSE);
		include_once($this->dir . "PARSEXML.php");
		$parseXML = new PARSEXML();
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
* @param	$citation		Array of global formatting data for citations
* @param	$footnote		Array of alternate creator formatting for footnotes
*/
	function getStyle($citation, $footnote)
	{
		$this->citationToArray($citation);
		$this->footnoteToArray($footnote);
// If endnote-style citations, need to ensure we get endnote references with BBCode intact and not parsed by bibformat()
		if($this->style['citationStyle'])
			$this->bibStyle->bibformat->output = 'noScan';
		if($this->style['citationStyle'] && ($this->style['endnoteStyle'] == 2)) // footnotes
			$this->bibStyle->bibformat->citationFootnote = TRUE;
/**
* Load localisations etc.
*/
		$this->loadArrays();
	}
/**
* Reformat the array representation of footnote creator styling into a more useable format.
*
* @author	Mark Grimshaw
* @version	1
*
* @param	$footnote		nodal array representation of XML data
* @return	flattened array representation for easier use.
*/
	function footnoteToArray($footnote)
	{
		foreach($footnote as $array)
		{
			if(array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array) && 
				($array['_NAME'] != 'resource'))
				$this->footnoteStyle[$array['_NAME']] = $array['_DATA'];
		}
	}
/**
* Reformat the array representation of citation into a more useable format.
*
* @author	Mark Grimshaw
* @version	1
*
* @param	$citation		nodal array representation of XML data
*/
	function citationToArray($citation)
	{
		foreach($citation as $array)
		{
			if(array_key_exists('_NAME', $array) && array_key_exists('_DATA', $array))
				$this->style[$array['_NAME']] = $array['_DATA'];
		}
		if($this->style['citationStyle']) // Endnote style citations
			$this->citationToArrayEndnoteStyle();
		else // In-text style citations
			$this->citationToArrayInTextStyle();
	}
/**
* Reformat the array representation of citation into a more useable format - Endnote style citations
*
* @author	Mark Grimshaw
* @version	1
*/
	function citationToArrayEndnoteStyle()
	{
		include_once("PARSESTYLE.php");
		$temp = PARSESTYLE::parseStringToArray('citationEndnoteInText',
			trim($this->style['templateEndnoteInText']), $this->styleMap);
// Ensure we have only valid fields.
		foreach($temp as $field => $value)
		{
			if(array_key_exists($field, $this->styleMap->citationEndnoteInText) || ($field == 'independent') ||
				($field == 'ultimate') || ($field == 'preliminaryText'))
				$this->templateEndnoteInText[$field] = $value;
		}
		if(isset($this->templateEndnoteInText))
			$this->parseIndependent($this->templateEndnoteInText);
		$temp = PARSESTYLE::parseStringToArray('citationEndnote',
			trim($this->style['templateEndnote']), $this->styleMap);
// Ensure we have only valid fields.
		foreach($temp as $field => $value)
		{
			if(array_key_exists($field, $this->styleMap->citationEndnote) || ($field == 'independent') ||
				($field == 'ultimate') || ($field == 'preliminaryText'))
				$this->templateEndnote[$field] = $value;
		}
		if(isset($this->templateEndnote))
			$this->parseIndependent($this->templateEndnote);
		$temp = PARSESTYLE::parseStringToArray('citationEndnote', trim($this->style['ibid']), $this->styleMap);
// Ensure we have only valid fields.
		foreach($temp as $field => $value)
		{
			if(array_key_exists($field, $this->styleMap->citationEndnote) || ($field == 'independent') ||
				($field == 'ultimate') || ($field == 'preliminaryText'))
				$this->templateIbid[$field] = $value;
		}
		if(isset($this->templateIbid))
			$this->parseIndependent($this->templateIbid);
		$temp = PARSESTYLE::parseStringToArray('citationEndnote', trim($this->style['idem']), $this->styleMap);
// Ensure we have only valid fields.
		foreach($temp as $field => $value)
		{
			if(array_key_exists($field, $this->styleMap->citationEndnote) || ($field == 'independent') ||
				($field == 'ultimate') || ($field == 'preliminaryText'))
				$this->templateIdem[$field] = $value;
		}
		if(isset($this->templateIdem))
			$this->parseIndependent($this->templateIdem);
		$temp = PARSESTYLE::parseStringToArray('citationEndnote', trim($this->style['opCit']), $this->styleMap);
// Ensure we have only valid fields.
		foreach($temp as $field => $value)
		{
			if(array_key_exists($field, $this->styleMap->citationEndnote) || ($field == 'independent') ||
				($field == 'ultimate') || ($field == 'preliminaryText'))
				$this->templateOpCit[$field] = $value;
		}
		if(isset($this->templateOpCit))
			$this->parseIndependent($this->templateOpCit);
	}
/**
* Reformat the array representation of citation into a more useable format - In-text style citations
*
* @author	Mark Grimshaw
* @version	1
*/
	function citationToArrayInTextStyle()
	{
//print_r($this->style); print "<P>";
		include_once("PARSESTYLE.php");
		$temp = PARSESTYLE::parseStringToArray('citation',
			trim($this->style['template']), $this->styleMap);
// Ensure we have only valid fields.
		foreach($temp as $field => $value)
		{
			if(array_key_exists($field, $this->styleMap->citation) || ($field == 'independent') ||
				($field == 'ultimate') || ($field == 'preliminaryText'))
				$this->template[$field] = $value;
		}
		if(isset($this->template))
			$this->parseIndependent($this->template);
		if(trim($this->style['followCreatorTemplate']))
		{
			$temp = PARSESTYLE::parseStringToArray('citation',
				trim($this->style['followCreatorTemplate']), $this->styleMap);
			foreach($temp as $field => $value)
			{
				if(array_key_exists($field, $this->styleMap->citation) || ($field == 'independent') ||
					($field == 'ultimate') || ($field == 'preliminaryText'))
					$this->followCreatorTemplate[$field] = $value;
			}
			$this->parseIndependent($this->followCreatorTemplate);
		}
		if(trim($this->style['consecutiveCreatorTemplate']))
		{
			$temp = PARSESTYLE::parseStringToArray('citation',
				trim($this->style['consecutiveCreatorTemplate']), $this->styleMap);
			foreach($temp as $field => $value)
			{
				if(array_key_exists($field, $this->styleMap->citation) || ($field == 'independent') ||
					($field == 'ultimate') || ($field == 'preliminaryText'))
					$this->consecutiveCreatorTemplate[$field] = $value;
			}
			$this->parseIndependent($this->consecutiveCreatorTemplate);
		}
		if(trim($this->style['subsequentCreatorTemplate']))
		{
			$temp = PARSESTYLE::parseStringToArray('citation',
				trim($this->style['subsequentCreatorTemplate']), $this->styleMap);
			foreach($temp as $field => $value)
			{
				if(array_key_exists($field, $this->styleMap->citation) || ($field == 'independent') ||
					($field == 'ultimate') || ($field == 'preliminaryText'))
					$this->subsequentCreatorTemplate[$field] = $value;
			}
			$this->parseIndependent($this->subsequentCreatorTemplate);
		}
		if(trim($this->style['ambiguousTemplate']))
		{
			$temp = PARSESTYLE::parseStringToArray('citation',
				trim($this->style['ambiguousTemplate']), $this->styleMap);
// Ensure we have only valid fields.
			foreach($temp as $field => $value)
			{
				if(array_key_exists($field, $this->styleMap->citation) || ($field == 'independent') ||
					($field == 'ultimate') || ($field == 'preliminaryText'))
					$this->ambiguousTemplate[$field] = $value;
			}
			$this->parseIndependent($this->ambiguousTemplate);
		}
// replacement citation templates for particular resource types
		foreach($this->styleMap->types as $type => $value)
		{
			$key = $type . "Template";
			if(array_key_exists($key, $this->style) && trim($this->style[$key]))
			{
				$temp = PARSESTYLE::parseStringToArray('citation',
					trim($this->style[$key]), $this->styleMap);
				foreach($temp as $field => $value)
				{
					if(array_key_exists($field, $this->styleMap->citation) || ($field == 'independent') ||
						($field == 'ultimate') || ($field == 'preliminaryText'))
						$this->{$key}[$field] = $value;
				}
				$this->parseIndependent($this->$key);
			}
		}
	}
/**
* Parse independent strings of templates
*
* @Author Mark Grimshaw
*
*@param Array
*/
	function parseIndependent(&$array)
	{
		if(array_key_exists('independent', $array))
		{
			$ind1 = $array['independent'];
			foreach($ind1 as $key => $value)
			{
				$split = split('_', $key);
				$ind2[$split[1]] = $value;
			}
			if(isset($ind2))
				$array['independent'] = $ind2;
		}
	}
/**
* Loop through $this->items
* 
* @author	Mark Grimshaw
* @version	1
*
* @return	Complete string ready for printing to the output medium.
*/
	function process()
	{
		if(!isset($this->output))
			$this->output = 'html';
		$this->export = new EXPORTFILTER($this, $this->output);
		if($this->style['citationStyle']) // Endnote style citations
			return $this->endnoteStyle();
		else // In-text tyle citations
			return $this->inTextStyle();
	}
/**
* Endnote style citations
* 
* @author	Mark Grimshaw
* @version	1
*
* @return	Complete string ready for printing to the output medium.
*/
	function endnoteStyle()
	{
		$pString = '';
		$multiples = $textAtoms = array();
		$this->multipleCitations = FALSE;
		foreach($this->items as $count => $this->item)
		{
			$this->id = $count;
			$this->ids[$count] = $this->item['id'];
			$text = '';
//print "$count: "; print_r($this->item); print "<BR>";
// If $this->items[$count + 1]['text'] is empty, this is the start or continuation of a multiple citation.
// If $this->items[$count + 1]['text'] is not empty, this is the start of a new citation with 'text' preceeding the citation
			if(array_key_exists($count + 1, $this->items) && !$this->items[$count + 1]['text']) // multiples
			{
// Grab the first citation of the multiple
				$textAtoms[] = $this->item['text'];
				$citation = $this->map($this->templateEndnoteInText);
				$multiples[$count] = $citation;
				$this->multipleCitations = TRUE;
				continue;
			}
			else if($this->multipleCitations) // last of multiple
			{
				$citation = $this->map($this->templateEndnoteInText);
				$multiples[$count] = $citation;
			}
			else // not multiple
			{
				$text = $this->item['text'];
				$citation = $this->map($this->templateEndnoteInText);
			}
			$this->multipleCitations = FALSE;
			if(!empty($multiples))
			{
				$textAtom = join('', $textAtoms);
				$citation = $this->multiple($multiples);
				$multiples = $textAtoms = array();
				$text .= $textAtom;
			}
			if($this->style['formatEndnoteInText'] == 1) // superscript
				$citation = '[sup]' . $this->style['firstCharsEndnoteInText'] . $citation . 
				$this->style['lastCharsEndnoteInText'] . '[/sup]';
			else if($this->style['formatEndnoteInText'] == 2) // subscript
				$citation = '[sub]' . $this->style['firstCharsEndnoteInText'] . $citation . 
				$this->style['lastCharsEndnoteInText'] . '[/sub]';
			else
				$citation = $this->style['firstCharsEndnoteInText'] . $citation . 
				$this->style['lastCharsEndnoteInText'];
			$pString .= $text . $this->export->format($citation);
		}
		return $pString;
	}
/**
* In-text style citations
* 
* @author	Mark Grimshaw
* @version	1
*
* @return	Complete string ready for printing to the output medium.
*/
	function inTextStyle()
	{
		$pString = '';
		$multiples = $textAtoms = array();
		$this->multipleCitations = FALSE;
		$this->disambiguate();
		$preText = $postText = $this->previousNameInSameSentenceId = $this->citationInSameSentence = FALSE;
		foreach($this->items as $count => $this->item)
		{
			$this->matchNameSplit = $this->matchNameSplitEtAl = $this->nameInSameSentence = FALSE;
// If this is a single citation or the start of a multiple set, get any preText and postText
			if(!$this->multipleCitations && array_key_exists('preText', $this->item))
			{
				$preText = $this->item['preText'];
				if(array_key_exists('postText', $this->item))
					$postText = $this->item['postText'];
			}
			$usingReplacementType = FALSE;
			if(isset($tempTemplate))
			{
				$this->template = $tempTemplate;
				unset($tempTemplate);
			}
			$this->ids[$count] = $this->item['id'];
//print "$count: "; print_r($this->item); print "<BR>";
			$text = '';
			$this->followCreatorTemplateUse = FALSE;
			if(array_key_exists('ambiguousTemplate', $this->item))
			{
				if($this->checkTemplateFields($this->ambiguousTemplate))
				{
					$tempTemplate = $this->template;
					$this->template = $this->ambiguousTemplate;
				}
			}
			else if($this->multipleCitations && array_key_exists($count, $this->creatorIds) &&
				isset($this->consecutiveCreatorTemplate) && array_key_exists($count - 1, $this->creatorIds) && 
				($this->creatorIds[$count] == $this->creatorIds[$count - 1]))
			{
				$this->consecutiveCreatorSep[] = $count;
				if($this->checkTemplateFields($this->consecutiveCreatorTemplate))
				{
					$tempTemplate = $this->template;
					$this->template = $this->consecutiveCreatorTemplate;
				}
			}
// Replacement templates for particular resource types.  Need to match this template to other replacement templates by removing fields if necessary
			$type = $this->item['type'] . "Template";
			if(isset($this->$type))
			{
				$usingReplacementType = TRUE;
				if(isset($tempTemplate)) // i.e. $this->template has already been replaced
				{
					foreach($this->$type as $key => $value)
					{
						if(($key == 'ultimate') || ($key == 'preliminaryText'))
							continue;
						if(!array_key_exists($key, $this->template))
							unset($this->{$type}[$key]);
					}
					if($this->checkTemplateFields($this->$type))
						$this->template = $this->$type;
				}
				else
				{
					if($this->checkTemplateFields($this->$type))
					{
						$tempTemplate = $this->template;
						$this->template = $this->$type;
					}
				}
			}
// If $this->items[$count + 1]['text'] is empty, this is the start or continuation of a multiple citation.
// If $this->items[$count + 1]['text'] is not empty, this is the start of a new citation with 'text' preceeding the citation
			if(array_key_exists($count + 1, $this->items) && !$this->items[$count + 1]['text']) // multiples
			{
// Grab the first citation of the multiple
				$textAtoms[] = $this->item['text'];
				$citation = $this->map($this->template);
// If $citation is empty, we want to return something so return the title
				if($citation == '')
					$citation = $this->item['title'];
				$multiples[$count] = $citation;
				$this->multipleCitations = TRUE;
				continue;
			}
			else if($this->multipleCitations) // last of multiple
			{
				$citation = $this->map($this->template);
// If $citation is empty, we want to return something so return the title
				if(($citation == '') && array_key_exists('title', $this->item))
					$citation = $this->item['title'];
				$multiples[$count] = $citation;
			}
			else // not multiple
			{
				$text = $this->item['text'];
// If single citation is in the same sentence as first creator surname, use followCreatorTemplate if specified.
				if(isset($this->item['firstCreatorSurname']))
					$this->sameSentence($text);
				if(isset($this->followCreatorTemplate) && $this->nameInSameSentence)
				{
					if(!$usingReplacementType)
					{
						if($this->checkTemplateFields($this->followCreatorTemplate))
						{
							$tempTemplate = $this->template;
							$this->template = $this->followCreatorTemplate;
						}
					}
					else
					{
						foreach($this->$type as $key => $value)
						{
							if(($key == 'ultimate') || ($key == 'preliminaryText'))
								continue;
							if(!array_key_exists($key, $this->followCreatorTemplate))
								unset($this->{$type}[$key]);
						}
						if($this->checkTemplateFields($this->$type))
							$this->template = $this->$type; // $tempTemplate already stored
					}
					unset($split);
				}
// If single subsequent citation later in the text, use subsequentCitationTemplate
				if(!$this->matchNameSplit && !$this->nameInSameSentence && 
					array_search($this->item['id'], $this->inTextDoneIds) !== FALSE)
				{
					if(isset($this->subsequentCreatorTemplate) && 
						$this->checkTemplateFields($this->subsequentCreatorTemplate))
					{
						$tempTemplate = $this->template;
						$this->template = $this->subsequentCreatorTemplate;
					}
				}
				$citation = $this->map($this->template);
// If $citation is empty, we want to return something so return the title
				if($citation == '')
				{
					$citation = $this->item['title'];
					$this->matchNameSplit = FALSE;
				}
			}
			$this->multipleCitations = FALSE;
			if(!empty($multiples))
			{
				$textAtom = join('', $textAtoms);
				$citation = $this->multiple($multiples);
				$multiples = $textAtoms = array();
				$text .= $textAtom;
			}
// APA-style split page number(s) from main citation
			if($this->pageSplitDone)
			{
				if($this->matchNameSplitEtAl)
					$pattern = "/(" . preg_quote($this->matchNameSplit) . ".*" . 
					preg_quote($this->matchNameSplitEtAl) . ")/U";
				else
					$pattern = "/(" . preg_quote($this->matchNameSplit) . ")/U";
				$text = preg_replace($pattern, "$1 " . $this->pageSplitDone, $text, 1);
			}
			$pString .= $text . ' ' . 
				$this->export->format($this->style['firstChars'] . 
				$preText . $citation . $postText . $this->export->format($this->style['lastChars']));
// reset
			$preText = $postText = FALSE;
			$this->inTextDoneIds[] = $this->item['id'];
		}
		return $pString;
	}
/**
* Discover if creator name(s) is in same sentence and split citation if requested.
* 
* @author	Mark Grimshaw
* @version	1
*
*/
	function sameSentence($text)
	{
// Is this citation in the same sentence as the previous citation and for the same resource?
		if(($this->item['id'] == $this->previousNameInSameSentenceId) &&
			!preg_match("/^\s*(&nbsp;)*\./U", $text))
		{
			$this->citationInSameSentence = $this->nameInSameSentence = $this->matchNameSplit = TRUE;
			return;
		}
		$etAlEnd = $surnameEtAl = $possessiveEnd = $possessive1 = $possessive2 = 
			$this->citationInSameSentence = FALSE;
		$storedSurname = $this->item['firstCreatorSurname'];
		$text = str_replace("&nbsp;", " ", $text);
		$split = explode('. ', 
			preg_replace("/\[.*\]|\[\/.*\]|<.*[>]+/Us", "", $text)); // strip BBCode and HTML temporarily
		$lastSplit = $split[sizeof($split) - 1];
// Perhaps we've split on the dot in 'et al.' or equivalent
		if((substr($this->textEtAl, -1) == '.') && 
			array_key_exists(sizeof($split) - 2, $split) && 
			(substr($split[sizeof($split) - 2], -(strlen($this->textEtAl) - 1)) == 
			preg_replace("/[.]$/", '', $this->textEtAl)))
		{
			$this->matchNameSplitEtAl = ' ' . $this->textEtAl;
			$lastSplit = $split[sizeof($split) - 2] . ' ' . $lastSplit;
		}
// Citation tag may immediately follow 'creatorName et al.'
		else if((substr($split[sizeof($split) - 1], -(strlen($this->textEtAl))) == $this->textEtAl))
		{
			$this->matchNameSplitEtAl = ' ' . $this->textEtAl;
			$patternsEnd[] = '(' . $storedSurname . $this->matchNameSplitEtAl . ')$';
		}
		else if(!$this->matchNameSplitEtAl)
			$patterns[] = "(" . $storedSurname . ' ' . $this->textEtAl . ")";
		$lastSplit = trim($lastSplit);
		if($this->possessive1)
		{
			$patterns[] = $possessive1 = $storedSurname . htmlentities($this->possessive1, ENT_QUOTES);
			$patternsEnd[] = $possessive1 . '$';
		}
		if($this->possessive2)
		{
			$patterns[] = $possessive2 = $storedSurname . htmlentities($this->possessive2, ENT_QUOTES);
			$patternsEnd[] = $possessive2 . '$';
		}
		$surnamePattern = $surnamePatternEnd = FALSE;
		$sizeSurname = sizeof($this->creatorSurnames[$this->item['id']]);
		if($sizeSurname > 1)
		{
			$surnamePattern = "(" . 
				$this->creatorSurnames[$this->item['id']][0] . ".*?" . 
				$this->creatorSurnames[$this->item['id']][--$sizeSurname];
			if($this->possessive1)
			{
				$patterns[] = $poss = $surnamePattern . htmlentities($this->possessive1, ENT_QUOTES) . ")";
				$patternsEnd[] = $poss . '$';
			}
			if($this->possessive2)
			{
				$patterns[] = $poss = $surnamePattern . htmlentities($this->possessive2, ENT_QUOTES) . ")";
				$patternsEnd[] = $poss . '$';
			}
			$patterns[] = $poss = $surnamePattern . ")";
			$patternsEnd[] = $poss . '$';
		}
		$patterns[] = $storedSurname;
		$pattern = join("|", $patterns);
		$patternsEnd[] = $storedSurname . '$';
		$patternEnd = join("|", $patternsEnd);
//print "$pattern : $patternEnd<P>";
		if(preg_match("/$pattern/", $lastSplit, $matchName))
		{
			if(array_key_exists('followCreatorPageSplit', $this->style) && 
			!preg_match("/$patternEnd/", $lastSplit))
				$this->matchNameSplit = $matchName[0];
			$this->nameInSameSentence = TRUE;
			$this->previousNameInSameSentenceId = $this->item['id'];
		}
		else
			$this->previousNameInSameSentenceId = FALSE;
	}
/**
* For any replacement templates used for in-text citations, check we have fields to populate it with.  If not, return FALSE to indicate that we use original $this->template
* 
* @author	Mark Grimshaw
* @version	1
*
*/
	function checkTemplateFields($template)
	{
		foreach($template as $key => $value)
		{
			if(array_key_exists($key, $this->item) || ($key == 'preliminaryText'))
				return TRUE; // use replacement template
		}
		return FALSE; // use original template
	}
/**
* Disambiguate any ambiguous citations
* 
* @author	Mark Grimshaw
* @version	1
*
*/
	function disambiguate()
	{
//print_r($this->items); print "<P>";
		if(!$this->style['ambiguous']) // do nothing
			return;
		$disambiguatedIds = $ambiguousTitles = $this->yearsDisambiguated = $this->bibliographyIds = array();
		foreach($this->items as $count => $item)
		{
			if(($this->style['ambiguous'] == 1) &&
			array_key_exists('title', $this->template) && array_key_exists('title', $item) && 
			array_key_exists('year', $this->template) && array_key_exists('year', $item))
			{
				foreach($this->titles as $titleIndex => $title)
				{
					if(($title == $item['title']) // same title
					&& ($this->creatorIds[$titleIndex] == $item['creatorIds']) // same creators
					&& ($this->years[$titleIndex] == $item['year']) // same year
					&& ($this->citationIds[$titleIndex] != $item['id'])) // not the same citation ID
					{
						$identifier = str_replace(' ', '', $title) . 
							str_replace(' ', '', $this->creatorIds[$titleIndex]);
						if(!array_key_exists($identifier, $ambiguousTitles))
							$ambiguousTitles[$identifier] = range('a', 'z'); // Start a new letter set
						$this->bibliographyIds[$item['id']] = $identifier;
						break;
					}
				}
			}
			else if(array_key_exists('year', $this->template) && array_key_exists('year', $item))
			{
				foreach($this->years as $yearIndex => $year)
				{
					if(array_key_exists('creatorIds', $item) 
					&& array_key_exists($yearIndex, $this->creatorIds) 
					&& ($year == $item['year']) // same year
					&& ($this->creatorIds[$yearIndex] == $item['creatorIds']) // same creators
					&& ($this->citationIds[$yearIndex] != $item['id'])) // not the same citation ID
					{
						if($this->style['ambiguous'] == 1) // add letter after year
						{
							$identifier = str_replace(' ', '', $year) . 
								str_replace(' ', '', $this->creatorIds[$yearIndex]);
							if(!array_key_exists($identifier, $ambiguousTitles))
								$ambiguousTitles[$identifier] = range('a', 'z'); // Start a new letter set
							$this->bibliographyIds[$item['id']] = $identifier;
						}
						else if($this->style['ambiguous'] == 2) // add title and new template
							$this->items[$count]['ambiguousTemplate'] = TRUE;
						break;
					}
				}
			}
		}
		foreach($this->bibliographyIds as $id => $identifier)
		{
			if(!$identifier)
				continue;
			else
			{
				foreach($this->items as $count => $item)
				{
					if($item['id'] == $id)
					{
						if(!array_key_exists($id, $this->yearsDisambiguated))
							$letter = array_shift($ambiguousTitles[$identifier]);
						$this->items[$count]['year'] .= $letter;
						$this->yearsDisambiguated[$id] =$this->items[$count]['year'];
					}
				}
			}
		}
		unset($this->titles);
		unset($this->pages);
		unset($this->years);
	}
/**
* Map the $item array against the style array and produce a string ready to be formatted for bold, italics etc.
* 
* @author	Mark Grimshaw
* @version	1
*
* @param	array template
* @return	string ready for printing to the output medium.
*/
	function map($template)
	{
//print_r($template); print "<P>";
//print_r($this->item); print "<BR>";
		$itemArray = array();
		$index = 0;
		$ultimate = $preliminaryText = '';
		$previousFieldExists = $nextFieldExists = TRUE;
		$pageSplit = FALSE;
		if(array_key_exists('independent', $template))
			$independent = $template['independent'];
/**
* For dependency on next field, we must grab array keys of citation template, shift the first element then, in the loop, 
* check each element exists in $item.  If it doesn't, $nextFieldExists is set to FALSE
*/
		$checkPost = array_keys($template);
		array_shift($checkPost);
		foreach($template as $key => $value)
		{
//print_r($value); print "<P>";
			if($key == 'ultimate')
			{
				$ultimate = $value;
				continue;
			}
			if($key == 'preliminaryText')
			{
				$preliminaryText = $value;
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
			if($this->matchNameSplit && ($key == 'pages')) // If pages split has occurred, remove dependencies for pages.
			{
				$pre = str_replace("__DEPENDENT_ON_PREVIOUS_FIELD__", '', $pre);
				$post = str_replace("__DEPENDENT_ON_PREVIOUS_FIELD__", '', $post);
			}
			else
			{
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
			}
/**
* Deal with __SINGULAR_PLURAL__ for pages
*/
			if($key == 'pages')
			{
				if(array_key_exists('pluralPagesExist', $this->item)) // plural alternative for this key
				{
					$pre = array_key_exists('plural', $value) ? 
						preg_replace("/__SINGULAR_PLURAL__/", $value['plural'], $pre) : $pre;
					$post = array_key_exists('plural', $value) ? 
						preg_replace("/__SINGULAR_PLURAL__/", $value['plural'], $post) : $post;
				}
				else // singular alternative for this key
				{
					$pre = array_key_exists('singular', $value) ? 
						preg_replace("/__SINGULAR_PLURAL__/", $value['singular'], $pre) : $pre;
					$post = array_key_exists('singular', $value) ? 
						preg_replace("/__SINGULAR_PLURAL__/", $value['singular'], $post) : $post;
				}
// Deal with en dash characters
				$this->item[$key] = $this->export->format($this->item[$key]);
			}
/**
* Make sure we don't have duplicate punctuation characters
*/			$lastPre = substr($post, -1);
			$firstItem = substr($this->item[$key], 0, 1);
			if($firstItem === $lastPre)
				$this->item[$key] = substr($this->item[$key], 1);
			$firstPost = substr($post, 0, 1);
			$lastItem = substr($this->item[$key], -1);
			if(preg_match("/[.,;:?!]/", $lastItem) && ($firstPost == $lastItem))
				$post = substr($post, 1); // take a guess at removing first character of $post
/**
* Strip backticks used in template
*/
			$pre = str_replace("`", '', $pre);
			$post = str_replace("`", '', $post);
			if($this->item[$key])
			{
// Endnote style citations 
				if(($key == 'id') && $this->style['citationStyle'])
					$itemArray[$index] = $this->formatCitationId($pre, $post);
				else // in-text citations
				{
					if($this->matchNameSplit)
						$pageSplit = $index;
					$itemArray[$index] = $pre . $this->item[$key] . $post;
				}
			}
			$previousFieldExists = $nextFieldExists = TRUE;
			$index++;
		}
/**
* Check for independent characters.  These (should) come in pairs.
*/		if(isset($independent))
		{
//print_r($independent); print "<P>"; 
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
		$this->pageSplitDone = FALSE;
		if(($pageSplit !== FALSE) && (sizeof($itemArray) > 1))
		{
			$index = $pageSplit;
			$pageSplit = $itemArray[$pageSplit];
			unset($itemArray[$index]);
			$this->pageSplitDone = TRUE;
// Second citation from same resource in same sentence.
			if($this->citationInSameSentence)
				return trim($pageSplit);
		}
		$pString = join('', $itemArray);
/**
* if last character is punctuation (which it may be with missing fields etc.), and $ultimate is also 
* punctuation, remove last character.
*/		if($ultimate)
		{
			$last = substr(trim($pString), -1);
/**
* Don't do ';' in case last element is URL with &gt; ...!
*/
			if(preg_match("/^[.,:?!]/", $ultimate) && preg_match("/[.,:?!]/", $last))
				$pString = substr(trim($pString), 0, -1);
		}
		if($this->pageSplitDone)
		{
			$this->pageSplitDone = trim(trim($pString) . $ultimate);
			if(preg_match("/[.,;:?!]$/", $this->pageSplitDone))
				$this->pageSplitDone = substr($this->pageSplitDone, 0, -1);
			$this->pageSplitDone = $this->export->format($this->style['firstChars']) . 
				$preliminaryText . $this->hyperLink($this->pageSplitDone) .  
				$this->export->format($this->style['lastChars']);
			return trim($pageSplit);
		}
		return $this->hyperLink($preliminaryText . trim($pString) . $ultimate);
	}
/**
* Format the citation ID for endnote-style citations
* @author	Mark Grimshaw
*
* @param	string pre-characters
* @param 	string post-characters
* @return	string formatted ID.
*/
	function formatCitationId($pre, $post)
	{
		if($this->style['endnoteStyle'] == 1) // Endnotes, same ids
			$id = $this->endnoteSameIdsArray[$this->item['id']];
		else // different incrementing ids (endnotes or footnotes)
			$id = $this->id;
		if($this->output != 'rtf')
			return $pre . $id . $post;
// First create the RTF stylesheet if not already done
		$text = '';
		if(!$this->styleSheet)
		{
// Stylesheet for hyperlinks, list bullets and endnotes
// \s16 for endnote text (in the body of the paper)
// \cs2 .. \cs18 for ordered endnotes
// \cs19 .. \cs21 for unordered endnotes
// \s22 for footnote text (in the body of the paper)
// \cs22 .. \cs24 for footnotes
			$this->styleSheet = "{\\stylesheet\n
{\\*\\cs1 Hyperlink;}\n
{\\*\\cs1 Bullet Symbols;}\n
{\\*\\cs1 Numbering Symbols;}\n";
// Set up RTF settings for endnotes and footnotes
			if($this->style['formatEndnoteInText'] == 1)
			{
				$format = "\\super";
				if($this->style['endnoteStyle'] == 2) // footnotes
					$this->styleSheet .= "{\\cs10\\super Footnote anchor;}\n";
				else
					$this->styleSheet .= "{\\cs10\\super Endnote anchor;}\n";
			}
			else if($this->style['formatEndnoteInText'] == 2)
			{
				$format = "\\sub";
				if($this->style['endnoteStyle'] == 2) // footnotes
					$this->styleSheet .= "{\\cs10\\sub Footnote anchor;}\n";
				else
					$this->styleSheet .= "{\\cs10\\sub Endnote anchor;}\n";
			}
			else
			{
				$format = "\\plain";
				if($this->style['endnoteStyle'] == 2) // footnotes
					$this->styleSheet .= "{\\cs10 Footnote anchor;}\n";
				else
					$this->styleSheet .= "{\\cs10 Endnote anchor;}\n";
			}
/* RTF notes:
/fet0 produces lo0wercase roman numerals
/fet1 produces arabic numerals
*/
			if($this->style['endnoteStyle'] == 0) // Incrementing endnotes
			{
				$this->styleSheet .= "{\\s2\\ql \\sbasedon0 endnote text;}\n";
				$this->styleSheet .= "{\\cs2 \\additive $format \\sbasedon10 endnote reference;}\n";
				$this->styleSheet .= "}\n\n\\aftnnar\\fet1\n\n";
			}
			else if($this->style['endnoteStyle'] == 1) // Endnotes, same ids
			{
				$this->styleSheet .= "{\\s2\\ql \\sbasedon0 endnote text;}\n";
				$this->styleSheet .= "{\\cs2 $format \\sbasedon10 endnote reference;}\n";
				$this->styleSheet .= "}\n\n\\aftnnar\\fet1\n\n";
			}
			else if($this->style['endnoteStyle'] == 2) // Incrementing footnotes
			{
				$this->styleSheet .= "{\\s2\\ql \\sbasedon0 footnote text;}\n";
				$this->styleSheet .= "{\\cs2 \\additive $format \\sbasedon10 footnote reference;}\n";
				$this->styleSheet .= "}\n\n\\aftnnar\\ftnbj\\fet1\n\n";
			}
		}
// Now formatting for RTF output
		$preId = $this->style['firstCharsEndnoteID'];
		$postId = $this->style['lastCharsEndnoteID'];
// RTF output.
// NB - Word and OO.org will print endnotes in the order in which they are presented in the text.  This is fine for different incrementing ids but bad when the ids 
// follow a specificed bibliography order; in this case, they are likely not to be in incrementing order in the text.  If, in the text, endnote ids are in the order 4, 6, 1, 3, 2, 5 
// for example, they will print out in the endnotes in that order if we use RTF's default endnote formatting.  If this is the case, we need to provide fake endnotes as a 
// plain string to be appended to the final RTF output.
		if($this->wikindx)
		{
			include_once("core/session/SESSION.php");
			$session = new SESSION();
// WIKINDX-specific:  Indentation of appended bibliography
			if($session->getVar('exportPaper_indentBib') == 'indentAll')
				$bf = "\\li720 ";
			else if($session->getVar('exportPaper_indentBib') == 'indentFL')
				$bf = "\\fi720 ";
			else if($session->getVar('exportPaper_indentBib') == 'indentNotFL')
				$bf = "\\li720\\fi-720 ";
			else
				$bf = "\\li1\\fi1 ";
// WIKINDX-specific:  Line spacing of appended bibliography
			if($session->getVar('exportPaper_spaceBib') == 'oneHalfSpace')
				$bf = "\\pard\\plain $bf\\sl360\\slmult1 ";
			else if($session->getVar('exportPaper_spaceBib') == 'doubleSpace')
				$bf = "\\pard\\plain $bf\\sl480\\slmult1 ";
			else
				$bf = "\\pard\\plain$bf";
// WIKINDX-specific:  Indentation of footnotes
			if($session->getVar('exportPaper_indentFt') == 'indentAll')
				$ftf = "\\li720 ";
			else if($session->getVar('exportPaper_indentFt') == 'indentFL')
				$ftf = "\\fi720 ";
			else if($session->getVar('exportPaper_indentFt') == 'indentNotFL')
				$ftf = "\\li720\\fi-720 ";
			else
				$ftf = "\\li1\\fi1 ";
// WIKINDX-specific:  Line spacing of footnotes
			if($session->getVar('exportPaper_spaceFt') == 'oneHalfSpace')
				$ftf = "\\pard\\plain $ftf\\sl360\\slmult1 ";
			else if($session->getVar('exportPaper_spaceFt') == 'doubleSpace')
				$ftf = "\\pard\\plain $ftf\\sl480\\slmult1 ";
			else
				$ftf = "\\pard\\plain$ftf";
		}
		else
			$bf = $ftf = "\\pard\\plain ";
// END WIKINDX-specific
		if($this->style['endnoteStyle'] == 0) // Endnotes, incrementing ids
		{
			$citation = "{\\cs2 $preId\\chftn $postId}{__OSBIB__ENDNOTE__$id}";
			$endnoteString = "{\\footnote\\ftnalt$bf\\s2\\ql " . $citation . "}}__WIKINDX__NEWLINE__";
			return "{\\cs2 \\chftn" . $endnoteString;
		}
		else if($this->style['endnoteStyle'] == 1) // Endnotes, same ids
		{
			if(array_search($id, $this->rtfDoneIds) === FALSE)
			{
				$this->rtfDoneIds[] = $id;
				if(array_key_exists('sameIdOrderBib', $this->style)) // provide fake endnotes
				{
					$citation = "{\\cs2 $preId$id$postId}{__OSBIB__ENDNOTE__$id}";
					$this->endnoteStringArray[$id] = $bf . $citation;
					if($this->style['formatEndnoteInText'] == 1)
						return "{\\cs2\\super $id}__WIKINDX__NEWLINE__";
					else if($this->style['formatEndnoteInText'] == 2)
						return "{\\cs2\\sub $id}__WIKINDX__NEWLINE__";
					else
						return "{\\cs2\\plain $id}__WIKINDX__NEWLINE__";
				}
				else // Not following bibliography order
				{
					$citation = "{\\cs2 $preId$id$postId}{__OSBIB__ENDNOTE__$id}";
					$endnoteString = "{\\footnote\\ftnalt$bf\\s2\\ql " . $citation . "}}__WIKINDX__NEWLINE__";
					return "{\\cs2 $id" . $endnoteString;
				}
			}
			else
			{
					$citation = "{\\cs2 $preId$id$postId}{__OSBIB__ENDNOTE__$id}";
					$endnoteString = "{\\footnote\\ftnalt$bf\\s2\\ql " . $citation . "}}__WIKINDX__NEWLINE__";
					return "{\\cs2 $id}";
			}
		}
		else if($this->style['endnoteStyle'] == 2) // Footnotes, incrementing ids
		{
			$citation = "{\\cs2 $preId\\chftn $postId}{__OSBIB__ENDNOTE__$id}";
			$endnoteString = "{\\footnote$ftf\\s2\\ql " . $citation . "}}__WIKINDX__NEWLINE__";
			return "{\\cs2 \\chftn" . $endnoteString;
		}
	}
/**
* Format hyperlinks and clean up citation
* @author	Mark Grimshaw
*
* @return	string ready for printing to the output medium.
*/
	function hyperLink($citation)
	{
// Ensure we have no preliminary punctuation left over
		$citation = preg_replace("/^\s*[.,;:]\s*/U", "", $citation);
		if($this->hyperlinkBase)
			$citation = MISC::a("link", $this->export->format(trim($citation)),
			$this->hyperlinkBase . $this->item['id']);
		return $citation;
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
* @param	$citationId
*/
	function formatNames($creators, $citationId)
	{
		if($this->bibStyle->bibformat->citationFootnote) // footnotes
		{
			list($pString, $creatorIds) = $this->bibStyle->bibformat->formatNames($creators, 'creator1', TRUE);
			$this->citationIds[$this->count] = $citationId;
			$this->creatorIds[$this->count] = join(',', $creatorIds);
			$this->items[$this->count]['creator'] = $this->creators[$this->count] = $pString;
			$this->items[$this->count]['creatorIds'] = $this->creatorIds[$this->count];
			return;
		}
		$style = $this->style;
		$first = TRUE;
/**
* Set default plural behaviour for creator lists
*/
		$pluralKey = "creator_plural";
		$this->$pluralKey = FALSE;
		$initialsStyle = 'creatorInitials';
		$firstNameInitial = 'creatorFirstName';
		$delimitTwo = 'twoCreatorsSep';
		$delimitFirstBetween = 'creatorSepFirstBetween';
		$delimitNextBetween = 'creatorSepNextBetween';
		$delimitLast = 'creatorSepNextLast';
		$uppercase = 'creatorUppercase';
		if(array_search($citationId, $this->citationIds) !== FALSE)
		{
			$list = 'creatorListSubsequent';
			$limit = 'creatorListSubsequentLimit';
			$moreThan = 'creatorListSubsequentMore';
			$abbreviation = 'creatorListSubsequentAbbreviation';
			$italics = 'creatorListSubsequentAbbreviationItalic';
		}
		else
		{
			$list = 'creatorList';
			$limit = 'creatorListLimit';
			$moreThan = 'creatorListMore';
			$abbreviation = 'creatorListAbbreviation';
			$italics = 'creatorListAbbreviationItalic';
		}
// cache surnames
		foreach($creators as $creatorIndex => $creator)
			$surnames[$creatorIndex] = $creator['surname'];
		if(!$this->style['citationStyle'] && // in-text style
			!array_key_exists($citationId, $this->creatorSurnames)) 
		{
			foreach($creators as $creatorIndex => $creator)
				$this->creatorSurnames[$citationId][] = $creator['surname'];
		}
		foreach($creators as $creatorIndex => $creator)
		{
			$creatorIds[] = $creator['id'];
			if($first)
			{
				$nameStyle = 'creatorStyle';
				$this->items[$this->count]['firstCreatorSurname'] = $creator['surname'];
				$first = FALSE;
			}
			else
				$nameStyle = 'creatorOtherStyle';
			$firstName = trim($this->checkInitials($creator, $style[$initialsStyle], 
				$style[$firstNameInitial]));
			$prefix = $creator['prefix'] ? trim(stripslashes($creator['prefix'])) . ' ' : '';
			if($style[$nameStyle] == 0) // Joe Bloggs
				$nameString = $firstName . ' ' . $prefix . stripslashes($creator['surname']);
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
// Distinguish between creators of the same surname within the same citation?
				$useInitials = FALSE;
				if(array_key_exists('useInitials', $this->style))
				{
					foreach($surnames as $surnameIndex => $surname)
					{
						if(($creator['surname'] == $surname) && ($surnameIndex != $creatorIndex))
						{
							if($style[$list] && $style[$limit]
							&& ($surnameIndex < $style[$moreThan])
							&& ($creatorIndex < $style[$moreThan]))
							{
								$useInitials = TRUE;
								break;
							}
						}
					}
				}
				if($useInitials)
				{
					$nameString = 
						stripslashes($creator['prefix']) . ' ' . 
						stripslashes($creator['surname']) . ' ' . 
						$firstName;
				}
				else
				{
					$nameString = 
						stripslashes($creator['prefix']) . ' ' . 
						stripslashes($creator['surname']);
				}
			}
			if(isset($style[$uppercase]))
				$nameString = $this->utf8->utf8_strtoupper($nameString);
			$cArray[] = trim($nameString);
		}
/**
* Keep only some elements in array if we've exceeded $moreThan
*/
		$etAl = FALSE;
		if($style[$list] && $style[$limit] && (sizeof($cArray) > $style[$moreThan]))
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
			$pluralKey = "creator_plural";
			$this->$pluralKey = TRUE;
		}
/**
* Finally flatten array
*/
		if($etAl)
			$pString = implode('', $cArray) . $etAl;
		else
			$pString = implode('', $cArray);
// Cache citation IDs
		$this->citationIds[$this->count] = $citationId;
		$this->creatorIds[$this->count] = join(',', $creatorIds);
		$this->items[$this->count]['creator'] = $this->creators[$this->count] = $pString;
		$this->items[$this->count]['creatorIds'] = $this->creatorIds[$this->count];
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
					$firstNameInitialMake = $this->utf8->utf8_strtoupper(substr(trim($name), 0, 1));
					$firstTime = FALSE;
				}
				else
					$initials[] = $this->utf8->utf8_strtoupper(substr(trim($name), 0, 1));
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
				$newString .= $this->utf8->utf8_strtolower($this->utf8->encodeUtf8($array[1])) . 
					$array[2];
				$pString = $array[4];
			}
			$newString .= $this->utf8->utf8_strtolower($this->utf8->encodeUtf8($pString));
		}
		$pString = isset($newString) ? $newString : $pString;
		$title = $this->utf8->utf8_ucfirst(trim($pString));
// remove extraneous {...}
		$title = preg_replace("/{(.*)}/U", "$1", $title);
		$this->items[$this->count]['title'] = $title;
		$this->titles[$this->count] = $this->items[$this->count]['title'];
	}
/**
* Format preText and postText.
* [cite]23:34-35|see ` for example[/cite] (as used by WIKINDX)
* 
* @author	Mark Grimshaw
* @version	1
*
* @param	string $preText
* @param	string $postText
*/
	function formatPrePostText($preText, $postText)
	{
		$this->items[$this->count]['preText'] = $preText;
		$this->items[$this->count]['postText'] = $postText;
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
* @param	$start	Page start.
* @param	$end		Page end.
* @return	string of pages.
*/
	function formatPages($start, $end = FALSE)
	{
		$style = $this->style;
/**
* If no page end, return just $start;
*/
		if(!$end)
		{
			$this->items[$this->count]['pages'] = $start;
			$this->pages[$this->count] = $this->items[$this->count]['pages'];
			return;
		}
/**
* Pages may be in roman numeral format etc.  Return unchanged
*/
		if(!is_numeric($start))
		{
			$this->items[$this->count]['pages'] = $start . 'WIKINDX_NDASH' . $end;
			$this->pages[$this->count] = $this->items[$this->count]['pages'];
			return;
		}
/**
* We have multiple pages...
*/
		$this->items[$this->count]['pluralPagesExist'] = TRUE;
/**
* They've done something wrong so give them back exactly what they entered
*/
		if(($end <= $start) || (strlen($end) < strlen($start)))
		{
			$this->items[$this->count]['pages'] = $start . 'WIKINDX_NDASH' . $end;
			$this->pages[$this->count] = $this->items[$this->count]['pages'];
			return;
		}
		else if($style['pageFormat'] == 2)
		{
			$this->items[$this->count]['pages'] = $start . 'WIKINDX_NDASH' . $end;
			$this->pages[$this->count] = $this->items[$this->count]['pages'];
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
						$this->items[$this->count]['pages'] = $start . 'WIKINDX_NDASH' . $endPage;
						$this->pages[$this->count] = $this->items[$this->count]['pages'];
						return;
					}
					if($endSub > $startPop)
						$endPage = $endSub . $endPage;
				}
			}
			else
			{
				$this->items[$this->count]['pages'] = $start . 'WIKINDX_NDASH' . $end;
				$this->pages[$this->count] = $this->items[$this->count]['pages'];
				return;
			}
		}
/**
* We should never reach here - in case we do, give back complete range so that something at least is printed
*/
		$this->items[$this->count]['pages'] = $start . 'WIKINDX_NDASH' . $end;
		$this->pages[$this->count] = $this->items[$this->count]['pages'];
	}
/**
* Format publication year.
* $this->style['yearFormat']:
* 0 == 1998
* 1 == '98
* 2 == 98
* 
* @author	Mark Grimshaw
* @version	1
*
* @param	$year
* @return	string of year.
*/
	function formatYear($year)
	{
		if(!$year)
		{
			$this->items[$this->count]['year'] = $this->years[$this->count] = $this->style['replaceYear'];
			return;
		}
		if(!$this->style['yearFormat']) // 1998
			$this->items[$this->count]['year'] = $year;
		else if($this->style['yearFormat'] == 1) // '98
		{
			if(strlen($year) == 4)
				$this->items[$this->count]['year'] = "'" . substr($year, -2, 2);
			else
				$this->items[$this->count]['year'] = $year;
		}
		else if($this->style['yearFormat'] == 2) // 98
		{
			if(strlen($year) == 4)
				$this->items[$this->count]['year'] = substr($year, -2, 2);
			else
				$this->items[$this->count]['year'] = $year;
		}
		$this->years[$this->count] = $this->items[$this->count]['year'];
	}
/**
* Format multiple citations
*
* @author	Mark Grimshaw
* @version	1
*
* @param	$array		Citations
* @return	string
*/
	function multiple($multiples)
	{
		$first = TRUE;
		foreach($multiples as $index => $citation)
		{
			if($first)
			{
				$text = $citation;
				$first = FALSE;
				continue;
			}
			if($this->style['citationStyle']) // Endote-style citations
				$separator = $this->style['consecutiveCitationEndnoteInTextSep'];
			else
			{
				if(!$first && array_search($index, $this->consecutiveCreatorSep) !== FALSE)
					$separator = $this->style['consecutiveCreatorSep'];
				else
					$separator = $this->style['consecutiveCitationSep'];
			}
			$text .= $separator . $citation;
		}
		return $text;
	}
/**
* Collate and format the bibliography for endnote-style citations.  Must be processed in the same order as $ids.
* Where the id nos. are the same for each resource (endnote-style citations), store the bibliographic id order with an incrementing citation id no.
*
* @author	Mark Grimshaw
* @version	1
*
* @param multiArray - multiple array of raw bibliographic data to be processed by $this->bibStyle
* @param array - resource ids giving order of processing bibliography
*/
	function processEndnoteBibliography($rows, $ids)
	{
		$this->export = new EXPORTFILTER($this, $this->output);
		$process = $this->bibStyleProcess;
		if(isset($this->pages))
			$pages = $this->pages;
		if($this->style['citationStyle']) // endnote-style
		{
// id numbers follow bibliography order for same ids
			if(($this->style['endnoteStyle'] == 1) && 
			array_key_exists('sameIdOrderBib', $this->style))
			{
				$index = 1;
				foreach($rows as $id => $row)
				{
					$this->endnoteCitations[$id] = $this->endnoteRemovePunc($this->bibStyle->$process($row));
					$this->endnoteSameIdsArray[$id] = $index;
					++$index;
				}
			}
			else
			{
				$endnoteSameIds = 1;
				if($this->style['endnoteStyle'] == 1) // Endnotes, same ids
				{
					foreach(array_unique($ids) as $id)
					{
						if(!array_key_exists($id, $this->endnoteCitations)) // don't have this one
							$this->endnoteCitations[$id] = 
							$this->endnoteRemovePunc($this->bibStyle->$process($rows[$id]));
						if(!array_key_exists($id, $this->endnoteSameIdsArray))
						{
							$this->endnoteSameIdsArray[$id] = $endnoteSameIds;
							++$endnoteSameIds;
						}
					}
				}
				else
				{
					$index = 1;
					foreach($ids as $id)
					{
						if(isset($pages) && array_key_exists($index, $this->pages) && 
						($this->style['endnoteStyle'] == 2)) // footnotes
						{
							$type = $rows[$id]['type'];
							$this->bibStyle->bibformat->footnotePages = $this->export->format($pages[$index]);
						}
						$this->endnoteCitations[] = 
							$this->endnoteRemovePunc($this->bibStyle->$process($rows[$id]));
						if(!array_key_exists($id, $this->endnoteSameIdsArray))
						{
							$this->endnoteSameIdsArray[$id] = $endnoteSameIds;
							++$endnoteSameIds;
						}
						$this->bibStyle->bibformat->footnotePages = FALSE;
						++$index;
					}
				}
			}
		}
	}
/**
* Removing trailing spaces and punctuation for endnote-style bibliographic entries.
*
* @author	Mark Grimshaw
* @version	1
*
* @param string
* @return string
*/
	function endnoteRemovePunc($entry)
	{
// probably don't want to remove trailing punctuation so currently just trim
		return trim($entry);
		return preg_replace("/[.,;:?!]$/", '', trim($entry));
	}
/**
* Format the bibliography for in-text-style citations.
*
* @author	Mark Grimshaw
* @version	1
*
* @param array - array of raw bibliographic data for one resource to be processed by $this->bibStyle
*/
	function processIntextBibliography($row)
	{
		$process = $this->bibStyleProcess;
		if($this->output == 'html')
			$this->intextBibliography[] = str_replace("&nbsp;", ' ', $this->bibStyle->$process($row));
		else
			$this->intextBibliography[] = $this->bibStyle->$process($row);
	}
/**
* Collate the bibliography array for in-text-style citations.
*
* @author	Mark Grimshaw
* @version	1
*
* @return	string
*/
	function collateIntextBibliography()
	{
		$pString = $this->export->newLine . $this->export->newLine . 
			join($this->export->newLine, $this->intextBibliography);
		if($this->output == 'rtf') // add a page break
			return "\n\\page\n$pString";
		return $pString;
	}
/**
* Print the bibliography for endnote-style citations.
*
* @author	Mark Grimshaw
* @version	1
*
* @param string
* @return	string
*/
	function printEndnoteBibliography($pString)
	{
		$this->endnoteProcess();
		if($this->output == 'html')
		{
			$pre = $post = FALSE;
			$pre .= $this->export->format($this->style['firstCharsEndnoteID']);
			$post = $this->export->format($this->style['lastCharsEndnoteID']) . $post;
			$endnotes = '';
			foreach($this->endnotes as $index => $string)
				$endnotes .= $pre . $index . $post . $string . $this->export->newLine;
			$pString .= $this->export->newLine . $this->export->newLine . $endnotes;
		}
		else if($this->output == 'rtf')
		{
			if(!empty($this->endnoteStringArray))
			{
				ksort($this->endnoteStringArray);
				foreach($this->endnoteStringArray as $index => $string)
				{
					$match = "__OSBIB__ENDNOTE__$index}";
					$this->endnoteString .= str_replace($match, $this->endnotes[$index], $string) . "}\\par";
				}
				$pString .= "\\par\\par\\par\\par\n" . $this->endnoteString;
			}
			else
			{
				foreach($this->endnotes as $index => $string)
				{
					$match = "__OSBIB__ENDNOTE__$index}";
					$pString = str_replace($match, $string . "}", $pString);
				}
			}
		}
		else
		{
			$endnotes = '';
			foreach($this->endnotes as $index => $string)
				$endnotes .= $index . '. ' . $string . $this->export->newLine;
			$pString .= $this->export->newLine . $this->export->newLine . $endnotes;
		}
// Turn off footnote templating in bibformat
		$this->bibStyle->bibformat->citationFootnote = FALSE;
		$this->bibStyle->bibformat->output = $this->output;
		return $pString;
	}
/**
* Format the endnotes for endnote-style citations
*
* @author	Mark Grimshaw
* @version	1
*
* @return	string
*/
	function endnoteProcess()
	{
		if(!isset($this->ids))
			return;
		$endnoteSameIdsArray = array_flip($this->endnoteSameIdsArray);
		$doneIds = array();
		$citationIndex = 1;
		foreach($this->ids as $index => $id)
		{
			$this->item = array();
// We're using the same ID number for citations from the same resource
			if($this->style['endnoteStyle'] == 1)
			{
				if(array_key_exists($id, $doneIds))
					continue;
				else
					$doneIds[$id] = TRUE;
// Use specified bibliographic order?
				if(array_key_exists('sameIdOrderBib', $this->style))
				{
					$id = array_shift($endnoteSameIdsArray);
					$this->item['id'] = $id;
					$this->item['citation'] = $this->endnoteCitations[$id];
				}
				else
				{
					$this->item['id'] = $id;
					$this->item['citation'] = $this->endnoteCitations[$id];
				}
				$this->endnotes[$citationIndex] = $this->export->format($this->map($this->templateEndnote));
				++$citationIndex;
			}
			else
			{
				$tempTemplate = array();
				$size = sizeof($this->opCit);
				$this->item['id'] = $this->id = $index;
				if(array_key_exists('pluralPagesExist', $this->items[$index]))
					$this->item['pluralPagesExist'] = $this->items[$index]['pluralPagesExist'];
				if(array_key_exists('pages', $this->items[$index]))
				{
					$this->item['pages'] = $thesePages = $this->items[$index]['pages'];
					$this->item['pages'] = $this->export->format($this->item['pages']);
				}
				else
					$thesePages = FALSE;
				if($this->style['idem'] && $size && ($this->opCit[$size - 1] == $id) && 
					isset($lastPages) && ($thesePages == $lastPages) && !empty($this->templateIbid))
				{
					if(array_key_exists('citation', $this->templateIbid))
						$this->item['citation'] = array_shift($this->endnoteCitations);
					else
					{
						if(array_key_exists('creator', $this->templateIbid))
							$this->item['creator'] = $this->creators[$index];
						if(array_key_exists('year', $this->templateIbid))
							$this->item['year'] = $this->years[$index];
						if(array_key_exists('title', $this->templateIbid))
							$this->item['title'] = $this->titles[$index];
						array_shift($this->endnoteCitations);
					}
					$tempTemplate = $this->templateEndnote;
					$this->templateEndnote = $this->templateIbid;
				}
				else if($this->style['idem'] && $size && ($this->opCit[$size - 1] == $id) && 
					isset($lastPages) && ($thesePages != $lastPages) && !empty($this->templateIdem))
				{
					if(array_key_exists('citation', $this->templateIdem))
						$this->item['citation'] = array_shift($this->endnoteCitations);
					else
					{
						if(array_key_exists('creator', $this->templateIdem))
							$this->item['creator'] = $this->creators[$index];
						if(array_key_exists('year', $this->templateIdem))
							$this->item['year'] = $this->years[$index];
						if(array_key_exists('title', $this->templateIdem))
							$this->item['title'] = $this->titles[$index];
						array_shift($this->endnoteCitations);
					}
					$tempTemplate = $this->templateEndnote;
					$this->templateEndnote = $this->templateIdem;
				}
				else if($this->style['opCit'] && $size && (array_search($id, $this->opCit) !== FALSE) &&
				 !empty($this->templateOpCit))
				{
					if(array_key_exists('citation', $this->templateOpCit))
						$this->item['citation'] = array_shift($this->endnoteCitations);
					else
					{
						if(array_key_exists('creator', $this->templateOpCit))
							$this->item['creator'] = $this->creators[$index];
						if(array_key_exists('year', $this->templateOpCit))
							$this->item['year'] = $this->years[$index];
						if(array_key_exists('title', $this->templateOpCit))
							$this->item['title'] = $this->titles[$index];
						array_shift($this->endnoteCitations);
					}
					$tempTemplate = $this->templateEndnote;
					$this->templateEndnote = $this->templateOpCit;
				}
				else
				{
					if(array_key_exists('citation', $this->templateEndnote))
						$this->item['citation'] = array_shift($this->endnoteCitations);
					else
					{
						if(array_key_exists('creator', $this->templateEndnote))
							$this->item['creator'] = $this->creators[$index];
						if(array_key_exists('year', $this->templateEndnote))
							$this->item['year'] = $this->years[$index];
						if(array_key_exists('title', $this->templateEndnote))
							$this->item['title'] = $this->titles[$index];
					}
				}
				$lastPages = $thesePages;
// If footnotes, uses 'pages' formatting from footnote template
				if($this->style['citationStyle'] && ($this->style['endnoteStyle'] == 2))
				{
					$type = $this->bibStyle->bibformat->footnoteTypeArray[$this->items[$index]['type']];
					if(array_key_exists('pages', $this->bibStyle->bibformat->$type)
					&& array_key_exists('pages', $this->templateEndnote))
						$this->templateEndnote['pages'] = $this->bibStyle->bibformat->{$type}['pages'];
				}
				$this->endnotes[$index] = $this->export->format($this->map($this->templateEndnote));
				$this->opCit[] = $id;
				if(!empty($tempTemplate))
					$this->templateEndnote = $tempTemplate;
			}
		}
		return $this->export->newLine . $this->export->newLine . join($this->export->newLine, $this->endnotes);
	}
/**
* Localisations etc.
* @author	Mark Grimshaw
* @version	1
*/
	function loadArrays()
	{
// WIKINDX-specific.  Months depend on the localisation set in the bibliographic style file.  'et al.' depends on the user's wikindx localisation.
		if($this->wikindx)
		{
			$styleLoc = FALSE;
/* // temp......
// Style localisation
			$languageDir = $this->style['localisation'];
			if(!is_dir("languages/$languageDir"))
				$languageDir = "en";
			include_once("languages/$languageDir/CONSTANTS.php");
			$class = "CONSTANTS_" . $languageDir;
			$this->wikindxLanguageClass = new $class();
			if(method_exists($this->wikindxLanguageClass, "monthToLongName")
				&& method_exists($this->wikindxLanguageClass, "monthToShortName"))
			{
				$this->longMonth = $this->wikindxLanguageClass->monthToLongName();
				$this->shortMonth = $this->wikindxLanguageClass->monthToShortName();
				$styleLoc = TRUE; // successfully localised
			}
*/
// temp.......
			$styleLoc = TRUE;
// User localisation
			include_once("core/session/SESSION.php");
			$session = new SESSION();
			if(!$languageDir = $session->getVar("setup_language"))
				$languageDir = "en";
			include_once("languages/$languageDir/CONSTANTS.php");
			$class = "CONSTANTS_" . $languageDir;
			$this->wikindxLanguageClass = new $class();
			if($styleLoc && isset($this->wikindxLanguageClass->textEtAl))
			{
				$this->textEtAl = $this->wikindxLanguageClass->textEtAl;
				$this->possessive1 = $this->wikindxLanguageClass->possessive1;
				$this->possessive2 = $this->wikindxLanguageClass->possessive2;
				return;
			}
		}
// Defaults.  Any localisation in external files as above should follow this format.
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
// Scan for occurrences of creator name(s) followed by 'et al.' when checking if surname(s) is in same sentence as citation.
// e.g. Grimshaw et al. state "blah blah blah" [cite]123:45-46[/cite].
		$this->textEtAl = 'et al.';
// Similarly, check for possessive form of a single creator name in the same sentence.  English has two forms (the second below for names that end in 's').
// If there is no equivalent possessive form in another language, set these to FALSE.
		$this->possessive1 = "'s";
		$this->possessive2 = "'";
	}
}
?>

