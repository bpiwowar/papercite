<?php
/**********************************************************************************
WIKINDX: Bibliographic Management system.
Copyright (C)

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the
Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

The WIKINDX Team 2004
sirfragalot@users.sourceforge.net
**********************************************************************************/
/*****
*	BIBLIOGRAPHY STYLE class
*	Format a resource for a bibliographic style.
*
*	$Header: /cvsroot/bibliophile/OSBib/format/BIBSTYLE.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
*****/
class BIBSTYLE
{
// Constructor
	function BIBSTYLE($db, $output)
	{
		$this->db = $db;
		include_once("core/session/SESSION.php");
		$this->session = new SESSION();
		include_once("core/styles/BIBFORMAT.php");
		$this->bibformat = new BIBFORMAT("core/styles/");
/**
* CSS class for highlighting search terms
*/
		$this->bibformat->patternHighlight = "highlight";
		include_once("core/html/MISC.php");
// get the bibliographic style
		if($output == 'rtf')
			$this->setupStyle = $this->session->getVar("exportRtf_style");
		else
			$this->setupStyle = $this->session->getVar("setup_style");
/**
* If our style arrays do not exist in session, parse the style file and write to session.  Loading and 
* parsing the XML file takes about 0.1 second (P4 system) and so is a significant slowdown.  
* Try to do this only once every time we use a style.  NB.  These are saved in session with 'cite_' and 'style_' 
* prefixes - creating/copying or editing a bibliographic style clears these arrays from the session which will 
* force a reload of the style here.
*/
		$styleInfo = $this->session->getVar("style_name");
		$styleCommon = unserialize(base64_decode($this->session->getVar("style_common")));
		$styleTypes = unserialize(base64_decode($this->session->getVar("style_types")));
// File not yet parsed or user's choice of style has changed so need to 
// load, parse and store to session
		if((!$styleInfo || !$styleCommon || !$styleTypes) 
			|| ($styleInfo != $this->setupStyle))
		{
			list($info, $citation, $styleCommon, $styleTypes) = 
				$this->bibformat->loadStyle("styles/bibliography/", $this->setupStyle);
			$this->session->setVar("style_name", $info['name']);
			$this->session->setVar("cite_citation", base64_encode(serialize($citation)));
			$this->session->setVar("style_common", base64_encode(serialize($styleCommon)));
			$this->session->setVar("style_types", base64_encode(serialize($styleTypes)));
			$this->session->delVar("style_edited");
		}
		$this->bibformat->getStyle($styleCommon, $styleTypes);
		$this->output = $output;
		$this->bibformat->output = $output;
	}
// Accept a SQL result row of raw bibliographic data and process it.
// We build up the $bibformat->item array with formatted parts from the raw $row
	function process($row)
	{
		$this->row = $row;
		$type = $row['type']; // WIKINDX type
// For WIKINDX, if type == book or book article and there exists both 'year1' and 'year2' in $row (entered as 
// publication year and reprint year respectively), then switch these around as 'year1' is 
// entered in the style template as 'originalPublicationYear' and 'year2' should be 'publicationYear'.
		if(($type == 'book') || ($type == 'book_article'))
		{
			$year2 = stripslashes($this->row['year2']);
			if($year2 && !$this->row['year1'])
			{
				$this->row['year1'] = $year2;
				unset($this->row['year2']);
			}
			else if($year2 && $this->row['year1'])
			{
				$this->row['year2'] = stripslashes($this->row['year1']);
				$this->row['year1'] = $year2;
			}
		}
		$this->row = $this->bibformat->preProcess($type, $this->row);
// Return $type is the OSBib resource type ($this->book, $this->web_article etc.) as used in STYLEMAP
		$type = $this->bibformat->type;
// Various types of creator
		for($index = 1; $index <= 5; $index++)
		{
			if(!$this->row['creator' . $index] || 
				!array_key_exists('creator' . $index, $this->bibformat->styleMap->$type))
				continue;
			if(array_key_exists('creator' . $index, $this->bibformat->styleMap->$type))
				$this->grabNames('creator' . $index);
		}
// The title of the resource
		$this->createTitle();
// edition
		if($editionKey = array_search('edition', $this->bibformat->styleMap->$type))
			$this->createEdition($editionKey);
// pageStart and pageEnd
		$this->pages = FALSE; // indicates not yet created pages for articles
		if(array_key_exists('pages', $this->bibformat->styleMap->$type))
			$this->createPages();
// Date
		if(array_key_exists('date', $this->bibformat->styleMap->$type))
			$this->createDate();
// runningTime for film/broadcast
		if(array_key_exists('runningTime', $this->bibformat->styleMap->$type))
			$this->createRunningTime();
// web_article URL
		if(array_key_exists('URL', $this->bibformat->styleMap->$type) && 
			($itemElement = $this->createUrl()))
			$this->bibformat->addItem($itemElement, 'URL', FALSE);
// the rest...  All other database resource fields that do not require special formatting/conversion.
		$this->bibformat->addAllOtherItems($this->row);
// We now have an array for this item where the keys match the key names of $this->styleMap->$type 
// where $type is book, journal_article, thesis etc. and are now ready to map this against the defined 
// bibliographic style for each resource ($this->book, $this->book_article etc.).
// This bibliographic style array not only provides the formatting and punctuation for each field but also 
// provides the order. If a field name does not exist in this style array, we print nothing.
		$pString = $this->bibformat->map();
// ordinals such as 5$^{th}$
		$pString = preg_replace_callback("/(\d+)\\$\^\{(.*)\}\\$/", array($this, "ordinals"), $pString);
// remove extraneous {...}
		return preg_replace("/{(.*)}/U", "$1", $pString);
	}
// callback for ordinals above
	function ordinals($matches)
	{
		if($this->output == 'html')
			return $matches[1] . "<sup>" . $matches[2] . "</sup>";
		else if($this->output == 'rtf')
			return $matches[1] . "{{\up5 " . $matches[2] . "}}";
		else
			return $matches[1] . $matches[2];
	}
// Create the resource title
	function createTitle()
	{
		$pString = stripslashes($this->row['noSort']) . ' ' . 
			stripslashes($this->row['title']);
		if($this->row['subtitle'])
			$pString .= ': ' . stripslashes($this->row['subtitle']);
// anything enclosed in {...} is to be left as is 
		$this->bibformat->formatTitle($pString, "{", "}");
	}
// Create the URL
	function createUrl()
	{
		if(!$this->row['url'])
			return FALSE;
		$url = ($this->output == 'html') ? htmlspecialchars(stripslashes($this->row['url'])) : 
			stripslashes($this->row['url']);
		unset($this->row['url']);
		if($this->output == 'html')
			return MISC::a('rLink', $url, $url, "_blank"); 
		else
			return $url;
	}
// Create date
	function createDate()
	{
		$startDay = isset($this->row['miscField2']) ? stripslashes($this->row['miscField2']) : FALSE;
		$startMonth = isset($this->row['miscField3']) ? stripslashes($this->row['miscField3']) : FALSE;
		unset($this->row['miscField2']);
		unset($this->row['miscField3']);
		$endDay = isset($this->row['miscField5']) ? stripslashes($this->row['miscField5']) : FALSE;
		$endMonth = isset($this->row['miscField6']) ? stripslashes($this->row['miscField6']) : FALSE;
		unset($this->row['miscField5']);
		unset($this->row['miscField6']);
		$startDay = ($startDay == 0) ? FALSE : $startDay;
		$startMonth = ($startMonth == 0) ? FALSE : $startMonth;
		if(!$startMonth)
			return;
		$endDay = ($endDay == 0) ? FALSE : $endDay;
		$endMonth = ($endMonth == 0) ? FALSE : $endMonth;
		$this->bibformat->formatDate($startDay, $startMonth, $endDay, $endMonth);
	}
// Create runningTime for film/broadcast
	function createRunningTime()
	{
		$minutes = stripslashes($this->row['miscField1']);
		$hours = stripslashes($this->row['miscField4']);
		if(!$hours && !$minutes)
			return;
		if(!$hours)
			$hours = 0;
		$this->bibformat->formatRunningTime($minutes, $hours);
	}
// Create the edition number
	function createEdition($editionKey)
	{
		if(!$this->row[$editionKey])
			return FALSE;
		$edition = stripslashes($this->row[$editionKey]);
		$this->bibformat->formatEdition($edition);
	}
// Create page start and page end
	function createPages()
	{
		if(!$this->row['pageStart'] || $this->pages) // empty field or page format already done
		{
			$this->pages = TRUE;
			return;
		}
		$this->pages = TRUE;
		$start = trim(stripslashes($this->row['pageStart']));
		$end = $this->row['pageEnd'] ? trim(stripslashes($this->row['pageEnd'])) : FALSE;
		$this->bibformat->formatPages($start, $end);
	}
// get names from database for creator, editor, translator etc.
	function grabNames($nameType)
	{
		$recordset = $this->db->select(array("WKX_creator"), array("surname", "firstname", 
			"initials", "prefix", "id"), 
			" WHERE FIND_IN_SET(" . $this->db->formatField("id") . ", " . 
			$this->db->tidyInput($this->row[$nameType]) . ")");
		$numNames = $this->db->numRows($recordset);
		$nameArray = array("surname", "firstname", "initials", "prefix");
// Reorder $row so that creator order is correct and not that returned by SQL
		$ids = explode(",", $this->row[$nameType]);
		while($row = $this->db->loopRecordSet($recordset))
			$rowSql[$row['id']] = $row;
		if(!isset($rowSql))
			return FALSE;
		foreach($ids as $id)
			$rowTemp[] = $rowSql[$id];
		$this->bibformat->formatNames($rowTemp, $nameType);
	}
// bad Input function
	function badInput($error)
	{
		include_once("core/html/CLOSE.php");
		new CLOSE($this->db, $error);
	}
}
?>
