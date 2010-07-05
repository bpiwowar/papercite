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
*	TEMPLATE PREVIEW class.
*
*	Preview bibliographic style templates.
*
*	$Header: /cvsroot/bibliophile/OSBib/create/PREVIEWSTYLE.php,v 1.1 2005/06/25 02:57:34 sirfragalot Exp $
*****/
class PREVIEWSTYLE
{
	function PREVIEWSTYLE($vars)
	{
		$this->vars = $vars;
		include_once("../format/BIBFORMAT.php");
		include_once("MISC.php");
		include_once("MESSAGES.php");
		$this->messages = new MESSAGES();
		include_once("ERRORS.php");
		$this->errors = new ERRORS();
		$this->bibformat = new BIBFORMAT(FALSE, FALSE, TRUE);
	}
/**
* display
*
* @author Mark Grimshaw
*/
	function display()
	{
		include_once("ADMINSTYLE.php");
		include_once("../STYLEMAP.php");
		$map = new STYLEMAP();
		$templateNameArray = split("_", stripslashes($this->vars['templateName']), 2);
		$type = $templateNameArray[1];
		$templateString = stripslashes($this->vars['templateString']);
		if(!$templateString)
			return $this->errors->text("inputError", "missing");
		$templateArray = ADMINSTYLE::parseStringToArray($type, $templateString, $map, TRUE);
		if(!$templateArray)
			return $this->errors->text("inputError", "invalid");
		$style = unserialize(stripslashes($this->vars['style']));
		foreach($style as $key => $value)
			$this->bibformat->style[str_replace("style_", "", $key)] = $value;
		if(array_key_exists('independent', $templateArray))
		{
			$temp = $templateArray['independent'];
			foreach($temp as $key => $value)
			{
				$split = split("_", $key);
				$independent[$split[1]] = $value;
			}
			$templateArray['independent'] = $independent;
		}
		$this->bibformat->$type = $templateArray;
//		print_r($this->bibformat->$type); print "<P>";
//		print_r($this->bibformat->style); print "<P>";
		$this->loadArrays($type);
		$pString = $this->process($type);
		return MISC::b($this->messages->text("resourceType", $type) . ":") . MISC::br() . $pString;
	}
// Process the example.
	function process($type)
	{
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
			$nameType = 'creator' . $index;
			if(array_key_exists($nameType, $this->bibformat->styleMap->$type))
				$this->bibformat->formatNames($this->$nameType, 'creator' . $index);
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
		return $matches[1] . "<sup>" . $matches[2] . "</sup>";
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
		$url = htmlspecialchars(stripslashes($this->row['url']));
		unset($this->row['url']);
		return MISC::a('rLink', $url, $url, "_blank");
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
/**
* Example values for  resources and creators
*/
	function loadArrays($type)
	{
// Some of these default values may be overridden depending on the resource type.
// The values here are the keys of resource type arrays in STYLEMAP.php
		$this->row = array(
					'noSort'			=>				"The",
					'title' 			=>				"{OSBib System}",
					'subtitle'			=>				"Bibliographic formatting as it should be",
					'year1'				=>				"2003", // publicationYear
					'year2'				=>				"2004", // reprintYear
					'year3'				=>				"2001-2003", // volume set publication year(s)
					'pageStart'			=>				"109",
					'pageEnd'			=>				"122",
					'miscField2'		=>				'21', // start day
					'miscField3'		=>				'8', // start month
					'miscField4'		=>				'12', // numberOfVolumes
					'field1'			=>				'The Software Series', // seriesTitle
					'field2'			=>				'3', // edition
					'field3'			=>				'9', // seriesNumber
					'field4'			=>				'III', // volumeNumber
					'field5'			=>				'35', // umber
					'url'				=>				'http://bibliophile.sourceforge.net',
					'isbn'				=>				'0-9876-123456',
					'publisherName'		=>				'Botswana Books',
					'publisherLocation'	=>				'Selebi Phikwe',
					'collectionTitle'	=>				'The Best of Open Source Software',
					'collectionTitleShort'	=>			'Best_OSS',
					);
		$authors = array(
					0	=>	array(
							'surname'		=>			'Grimshaw',
							'firstname'		=>			'Mark',
							'initials'		=>			'N',
							'prefix'		=>			'',
							),
					1	=>	array(
							'surname'		=>			'Boulanger',
							'firstname'		=>			'Christian',
							'initials'		=>			'',
							'prefix'		=>			'',
							),
					2	=>	array(
							'surname'		=>			'Rossato',
							'firstname'		=>			'Andrea',
							'initials'		=>			'',
							'prefix'		=>			'',
							),
					4	=>	array(
							'surname'		=>			'Guillaume',
							'firstname'		=>			'Gardey',
							'initials'		=>			'',
							'prefix'		=>			'',
							),
					);
		$editors = array(
					0	=>	array(
							'surname'		=>			'Mouse',
							'firstname'		=>			'Mickey',
							'initials'		=>			'',
							'prefix'		=>			'',
							),
					1	=>	array(
							'surname'		=>			'Duck',
							'firstname'		=>			'Donald',
							'initials'		=>			'D D',
							'prefix'		=>			'de',
							),
					);
		$revisers = array(
					0	=>	array(
							'surname'		=>			'Bush',
							'firstname'		=>			'George',
							'initials'		=>			'W',
							'prefix'		=>			'',
							),
					);
		$translators = array(
					0	=>	array(
							'surname'		=>			'Lenin',
							'firstname'		=>			'V I',
							'initials'		=>			'',
							'prefix'		=>			'',
							),
					);
		$seriesEditors = array(
					0	=>	array(
							'surname'		=>			'Freud',
							'firstname'		=>			'S',
							'initials'		=>			'',
							'prefix'		=>			'',
							),
					);
		$composers = array(
					0	=>	array(
							'surname'		=>			'Mozart',
							'firstname'		=>			'Wolfgang Amadeus',
							'initials'		=>			'',
							'prefix'		=>			'',
							),
					);
		$performers = array(
					0	=>	array(
							'surname'		=>			'Led Zeppelin',
							'firstname'		=>			'',
							'initials'		=>			'',
							'prefix'		=>			'',
							),
					);
		$artists = array(
					0	=>	array(
							'surname'		=>			'Vinci',
							'firstname'		=>			'Leonardo',
							'initials'		=>			'',
							'prefix'		=>			'da',
							),
					);
		$this->creator1 = $authors;
		$this->creator2 = $editors;
		$this->creator3 = $revisers;
		$this->creator4 = $translators;
		$this->creator5 = $seriesEditors;
// For various types, override default settings above
		if($type == 'genericMisc')
		{
			$this->row['field2'] = 'software';
			$this->row['subtitle'] = '';
			$this->row['miscField2'] = '';
			$this->row['publisherName'] = 'Kalahari Soft';
		}
		else if ($type == 'magazine_article')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = '{OSS} Between the Sheets';
			$this->row['collectionTitle'] = 'The Scandal Rag';
			$this->row['collectionTitleShort'] = 'RAG';
			$this->row['field2'] = 'interview';
			$this->row['field4'] = 'Winter';
			$this->row['miscField5'] = '27'; // end day
			$this->row['miscField6'] = '8'; // end month
		}
		else if ($type == 'journal_article')
		{
			$this->row['field1'] = '23'; // volume number
			$this->row['miscField2'] = '';
			$this->row['miscField6'] = '9'; // end month
		}
		else if ($type == 'newspaper_article')
		{
			$this->row['field1'] = 'G2'; // section
			$this->row['field2'] = 'Gabarone';
			$this->row['collectionTitle'] = 'TseTswana Times';
			$this->row['collectionTitleShort'] = 'TsTimes';
		}
		else if ($type == 'proceedings')
		{
			$this->row['publisherName'] = 'International Association of Open Source Software';
			$this->row['publisherLocation'] = 'Serowe';
			$this->row['miscField5'] = '3'; // end day
			$this->row['miscField6'] = '9'; // end month
		}
		else if ($type == 'conference_paper')
		{
			$this->row['publisherName'] = 'International Association of Open Source Software';
			$this->row['publisherLocation'] = 'Serowe';
		}
		else if ($type == 'proceedings_article')
		{
			$this->row['publisherName'] = 'International Association of Open Source Software';
			$this->row['publisherLocation'] = 'Serowe';
			$this->row['miscField5'] = '3'; // end day
			$this->row['miscField6'] = '9'; // end month
			$this->row['collectionTitle'] = '7th. International OSS Conference';
			$this->row['collectionTitleShort'] = '7_IntOSS';
		}
		else if ($type == 'thesis')
		{
			$this->row['field1'] = 'PhD';
			$this->row['field2'] = 'thesis';
			$this->row['field5'] = 'Pie in the Sky'; // Dept.
			$this->row['publisherName'] = 'University of Bums on Seats';
			$this->row['publisherLocation'] = 'Laputia';
		}
		else if ($type == 'web_article')
		{
			$this->row['field1'] = '23';
		}
		else if ($type == 'film')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Kill Will Vol. 3';
			$this->row['publisherName'] = 'Totally Brain Dead Films';
			$this->row['publisherLocation'] = '';
			$this->row['field1'] = 'USA';
			$this->row['miscField1'] = '59'; // minutes
			$this->row['miscField4'] = '5'; // hours
		}
		else if ($type == 'broadcast')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'We put people on TV and humiliate them';
			$this->row['publisherName'] = 'Lowest Common Denominator Productions';
			$this->row['publisherLocation'] = 'USA';
			$this->row['miscField1'] = '45'; // minutes
			$this->row['miscField4'] = ''; // hours
		}
		else if ($type == 'music_album')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = 'Canon & Gigue';
			$this->row['title'] = 'Pachelbel';
			$this->row['isbn'] = '447-285-2';
			$this->row['publisherName'] = 'Archiv';
			$this->row['field2'] = 'CD'; // medium
			$this->row['year1'] = '1982-1983';
		}
		else if ($type == 'music_track')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Dazed and Confused';
			$this->row['collectionTitle'] = 'Led Zeppelin 1';
			$this->row['collectionTitleShort'] = 'LZ1';
			$this->row['isbn'] = '7567826322';
			$this->row['publisherName'] = 'Atlantic';
			$this->row['field2'] = 'CD'; // medium
			$this->row['year1'] = '1994';
		}
		else if ($type == 'music_score')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Sonata in A Minor';
			$this->row['isbn'] = '3801 05945';
			$this->row['publisherName'] = 'Alfred Publishing';
			$this->row['publisherLocation'] = 'New York';
			$this->row['year1'] = '1994';
		}
		else if ($type == 'artwork')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Art? What Art?';
			$this->row['publisherName'] = 'More Money than Sense';
			$this->row['publisherLocation'] = 'New York';
			$this->row['field2'] = 'Movement in protoplasma';
			$this->creator1 = $artists;
		}
		else if ($type == 'software')
		{
			$this->row['field2'] = 'PHP source code'; // type
			$this->row['field4'] = '1.3'; // version
			$this->row['publisherName'] = 'Kalahari Soft';
			$this->row['publisherLocation'] = 'Maun';
		}
		else if ($type == 'audiovisual')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Whispering Sands';
			$this->row['field1'] = 'Chobe ArtWorks Series'; // series title
			$this->row['field2'] = 'video installation'; //medium
			$this->row['field4'] = 'IV'; // series number
			$this->row['publisherName'] = 'Ephemera';
			$this->row['publisherLocation'] = 'Maun';
			$this->creator1 = $artists;
		}
		else if ($type == 'database')
		{
			$this->row['noSort'] = 'The';
			$this->row['subtitle'] = 'Sotware Listings';
			$this->row['title'] = 'Blue Pages';
			$this->row['publisherName'] = 'Kalahari Soft';
			$this->row['publisherLocation'] = 'Maun';
		}
		else if ($type == 'government_report')
		{
			$this->row['noSort'] = 'The';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'State of Things to Come';
			$this->row['field1'] = 'Prognostications'; // section
			$this->row['field2'] = 'Pie in the Sky'; // department
			$this->row['publisherName'] = 'United Nations';
		}
		else if ($type == 'hearing')
		{
			$this->row['field1'] = 'Committee on Unworldly Activities'; // committee
			$this->row['field2'] = 'United Nations'; // legislative body
			$this->row['field3'] = 'Summer'; //session
			$this->row['field4'] = '113'; // document number
			$this->row['miscField4'] = '27'; // no. of volumes
		}
		else if ($type == 'statute')
		{
			$this->row['field1'] = '101.43a'; // public law no.
			$this->row['field2'] = 'Lex Hammurabi'; // code
			$this->row['field3'] = 'Autumn'; //session
			$this->row['field4'] = '34-A'; // section
			$this->row['year1'] = '1563 BC';
		}
		else if ($type == 'legal_ruling')
		{
			$this->row['noSort'] = 'The';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'People v. George';
			$this->row['field1'] = 'Court of Public Law'; // section
			$this->row['field2'] = 'Appellate Decision'; // type
			$this->row['publisherName'] = 'Legal Pulp';
			$this->row['publisherLocation'] = 'Gabarone';
		}
		else if ($type == 'case')
		{
			$this->row['noSort'] = 'The';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'People v. George';
			$this->row['field1'] = 'Public Law'; // reporter
			$this->row['field4'] = 'XIV'; // reporter volume
			$this->row['publisherName'] = 'Supreme Court';
		}
		else if ($type == 'bill')
		{
			$this->row['noSort'] = 'The';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'People v. George';
			$this->row['field1'] = 'Court of Public Law'; // section
			$this->row['field2'] = 'Lex Hammurabi'; // code
			$this->row['field4'] = 'Spring'; // session
			$this->row['publisherName'] = 'United Nations';
			$this->row['publisherLocation'] = 'New York';
		}
		else if ($type == 'patent')
		{
			$this->row['field1'] = 'Journal of Patents'; // publishedSource
			$this->row['field3'] = '289763[e].x-233'; // application no.
			$this->row['field4'] = 'bibliographic software'; // type
			$this->row['field5'] = '5564763[E].X-233'; // int. pat. no.
			$this->row['field6'] = 'OSBib'; // int. title
			$this->row['field7'] = 'software'; // int. class
			$this->row['field8'] = '0-84784-AAH.z'; // pat. no.
			$this->row['field9'] = 'not awarded'; // legal status
			$this->row['publisherName'] = 'Lawyers Inc.'; // assignee
			$this->row['publisherLocation'] = 'New Zealand';
		}
		else if ($type == 'personal')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Save up to 80% on Microsoft Products!';
			$this->row['field2'] = 'email'; // type
		}
		else if ($type == 'unpublished')
		{
			$this->row['field2'] = 'manuscript'; // type
			$this->row['publisherName'] = 'University of Bums on Seats';
			$this->row['publisherLocation'] = 'Laputia';
		}
		else if ($type == 'classical')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Sed quis custodiet ipsos custodes?';
			$this->row['field4'] = 'Codex XIX'; // volume
			$this->row['year1'] = '114 BC'; // volume
		}
		else if ($type == 'manuscript')
		{
			$this->row['field2'] = 'manuscript'; // type
			$this->row['publisherName'] = 'University of Bums on Seats';
			$this->row['publisherLocation'] = 'Laputia';
		}
		else if ($type == 'map')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Mappa Mundi';
			$this->row['field1'] = 'Maps of the World'; // series title
			$this->row['field2'] = 'isomorphic projection'; // type
		}
		else if ($type == 'chart')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Incidence of Sniffles in the New York Area';
			$this->row['field1'] = 'sniff_1.gif'; // filename
			$this->row['field2'] = 'The GIMP'; // program
			$this->row['field3'] = '800*600'; // size
			$this->row['field4'] = 'GIF'; // type
			$this->row['field5'] = '1.1a'; // version
			$this->row['field6'] = '11'; // number
			$this->row['publisherName'] = 'University of Bums on Seats';
			$this->row['publisherLocation'] = 'Laputia';
		}
		else if ($type == 'miscellaneous')
		{
			$this->row['noSort'] = '';
			$this->row['subtitle'] = '';
			$this->row['title'] = 'Making Sunlight from Cucumbers';
			$this->row['field2'] = 'thin air'; // medium
			$this->row['publisherName'] = 'University of Bums on Seats';
			$this->row['publisherLocation'] = 'Laputia';
		}
	}
}
?>
