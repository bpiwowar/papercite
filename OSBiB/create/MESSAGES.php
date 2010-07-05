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
/**
*	Interface messages
*
*	@author Mark Grimshaw
*
*	$Header: /cvsroot/bibliophile/OSBib/create/MESSAGES.php,v 1.1 2005/06/20 22:26:51 sirfragalot Exp $
*/
class MESSAGES
{
// Constructor
	function MESSAGES()
	{
	}
/**
* Print the message
*/
	function text($arrayName, $indexName, $extra = FALSE)
	{
		include_once("MISC.php");
		include_once("../UTF8.php");
		$utf8 = new UTF8();
		$arrays = $this->loadArrays();
		$string = $arrays[$arrayName][$indexName];
		$string = $extra ?	preg_replace("/###/", $utf8->smartUtf8_decode($extra), $string) :
			preg_replace("/###/", "", $string);
// Display hints as per the CSS hint class.
		if($arrayName == 'hint')
			$string = MISC::span($string, "hint");
		return $utf8->encodeUtf8($string);
	}
// English messages
	function loadArrays()
	{
		return array(
		"heading" => array(
				"styles"		=>	"Styles###",
				"helpStyles"	=>	"Bibliographic Style Creation and Editing",
			),
// Hint messages
		"hint" => array(
				"styleShortName"	=>	"(No spaces)",
				"caseSensitive"	=>	"(Fields are case-sensitive)",
				"integer"	=>	"Integer",
			),
// Miscellaneous items that don't fit anywhere else
		"misc" => array(
// In select boxes - when it is not necessary to choose an existing selection.  WIKINDX will skip over this one. 
// Could be '---'
				'ignore'	=>	"IGNORE",
// This next one is required in BIBTEXPARSE - whatever the language, NEITHER THE KEY NOR THE VALUE SHOULD BE CHANGED!
// Leave as is!
// Leave as is!
// Leave as is!
				'IGNORE'	=>	"ignore",
// continue....
				"edited"	=>	"edited",
				"added"		=>	"added",
				"deleted"	=>	"deleted",
				"add"		=>	"add",
// Used in SUCCESS.php when a user chooses a user bibliography to browse.  The message is "Successfully set Bibliography".
				"set"		=>	"set",
				"top"		=>	"Top",
			),
// Mapping WKX_resource.type to description.
		"resourceType" => array(
				'book'			=>	"Book",
				'book_article'		=>	"Book Chapter",
				'web_article'		=>	"Internet",
				'journal_article'	=>	"Journal Article",
				'newspaper_article'	=>	"Newspaper Article",
				'thesis'		=>	"Thesis/Dissertation",
				'proceedings_article'	=>	"Proceedings Article",
// TV or Radio broadcast
				'broadcast'		=>	'Broadcast',
				'film'			=>	"Film",
// Legal Ruling or Regulation
				'legal_ruling'		=>	"Legal Rule/Regulation",
// Computer software
				"software"	=>	"Software",
// Art etc.
				"artwork"	=>	"Artwork",
// Audiovisual material
				"audiovisual"	=>	"Audiovisual",
// Legal cases
				"case"		=>	"Legal Case",
// Parliamentary bill (law)
				"bill"		=>	"Bill",
// Classical (historical) work
				"classical"	=>	"Classical Work",
				"conference_paper"	=>	"Conference Paper",
// Reports or documentation
				"report"	=>	"Report/Documentation",
// Government report or documentation
				"government_report"	=>	"Government Report/Documentation",
// Legal/Government Hearing
				"hearing"	=>	"Hearing",
// Online databases
				"database"	=>	"Online Database",
				"magazine_article"	=>	"Magazine Article",
				"manuscript"	=>	"Manuscript",
// Maps
				"map"		=>	"Map",
// Charts/images
				"chart"		=>	"Chart/Image",
// Statute
				"statute"	=>	"Statute",
// Patents
				"patent"	=>	"Patent",
// Personal Communication
				"personal"	=>	"Personal Communication",
// Unpublished work
				"unpublished"	=>	"Unpublished Work",
// Conference proceedings (complete set)
				"proceedings"	=>	"Proceedings",
// Music
				"music_album"	=>	"Recorded Music Album",
				"music_track"	=>	"Recorded Music Track",
				"music_score"	=>	"Music Score",
// For anything else that does not fit into the above categories.
				'miscellaneous'		=>	"Miscellaneous",
// Generic resource types used when creating bibliographic styles.
				"genericBook"		=>	"Generic book-type",
				"genericArticle"	=>	"Generic article-type",
				"genericMisc"		=>	"Generic miscellaneous",
			),
// Form submit button text
		"submit" => array(
				"Submit"		=>	"Submit",
				"Add"			=>	"Add",
				"Delete"		=>	"Delete",
				"Confirm"		=>	"Confirm",
				"Edit"			=>	"Edit",
				"Proceed to Confirm"	=>	"Proceed to Confirm",
				"List"			=>	"List",
				"Proceed"		=>	"Proceed",
				"Search"		=>	"Search",
				"Select"		=>	"Select",
// Add citation 
				"Cite"			=>	"Cite",
// Reset button for forms
				"reset"			=>	"Reset",
			),
// Messages for adding citations to quotes, notes, musings , comments etc. and for administration of 
// citation templates within bibliographic style creation/editing
		"cite" => array(
// The displayed hyperlink next to the textarea form input
				"cite"			=>	"Cite",
				"citationFormat"	=>	"Citation Formatting",
// In-text citation style as opposed to footnote style citations.
				"citationFormatInText"	=>	"In-text style",
				"citationFormatFootnote"	=>	"Footnote style",
				"creatorList"		=>	"Creator list abbreviation",
				"creatorListSubsequent"	=>	"Creator list abbreviation (subsequent appearances)",
				"creatorSep"		=>	"Creator delimiters",
				"creatorStyle" 		=>	"Creator style",
				"lastName"		=>	"Last name only",
// 'Last name only' is a choice in a select box and should not be translated
				"useInitials"		=>	"If 'Last name only', use initials to differentiate between creators with the same surname",
				"consecutiveCreator"	=>	"For consecutive citations by the same creator(s)",
				"omitCreator"		=>	"Omit creator list",
				"printCreator"		=>	"Print creator list",
// 'Omit creator list' is a choice in a select box and should not be translated as above
				"consecutiveCreatorSep"	=>	"If 'Omit creator list', separate citations with",
// The template is something like '(author|, year)' that the user is asked to enter
				"template"		=>	"Template",
				"consecutiveCitationSep" =>	"Separate consecutive citations with",
// Formatting of years
				"yearFormat"		=>	"Year format",
// Superscripting of citation
				"superscript"		=>	"Superscript",
// Ambiguous citations
				"ambiguous"		=>	"Ambiguous citations",
				"ambiguousFull"		=>	"Use the full name or initials",
				"ambiguousMore"		=>	"Add more creator names",
				"ambiguousTitle"	=>	"Add the title",
				"ambiguousYear"		=>	"Add a letter after the year",
				"ambiguousNameFormat"	=>	"Name format",
				"ambiguousYearFormat"	=>	"Year format",
// For footnote-style citations
				"footnoteStyleBib"	=>	"Format like bibliography",
				"footnoteStyleInText"	=>	"Format like in-text citations",
				"ibid"		=>	"Replace consecutive citations for the same resource and the same page with",
				"idem"		=>	"Replace consecutive citations for the same resource but a different page with",
				"opCit"		=>	"Replace previously cited resources with",
// Insert citation page number before or after footnote-style citation?
				"footnotePagePosition"	=>	"Position of citation page number",
				"footnotePageBefore"	=>	"Before citation",
				"footnotePageAfter"		=>	"After citation",
// For 'ibid' (by default does not display citation page numbers)
				"ibidPage"	=>	"Always display citation page number(s)",
// How to format the citation pages in footnote-style citations
				"footnoteCitationPageFormat" => "Format the citation page(s)",
				"footnoteCitationPageFormatNever" => "Never print citation page(s)",
				"footnoteCitationPageFormatBib"	=>	"Same as the bibliographic templates",
				"footnoteCitationPageFormatTemplate" => "Use the template below",
			),
// Administration of bibliographic styles
		"style" => array(
				"addLabel"		=>	"Add a Style",
				"copyLabel"		=>	"Copy a Style",
				"editLabel"		=>	"Edit Styles",
				"shortName"		=>	"Short Name",
				"longName"		=>	"Long Name",
				"primaryCreatorSep"	=>	"Primary creator delimiters",
				"otherCreatorSep"	=>	"Other creator delimiters",
				"ifOnlyTwoCreators"	=>	"If only two creators",
				"creatorSepBetween"	=>	"between",
				"creatorSepLast"	=>	"before last",
				"sepCreatorsFirst"	=>	"Between first two creators",
				"sepCreatorsNext"	=>	"Between following creators",
				"primaryCreatorStyle" 	=>	"Primary creator style",
				"otherCreatorStyle"	=>	"Other creator styles",
				"creatorFirstStyle" 	=>	"First",
				"creatorOthers"		=>	"Others",
				"creatorInitials"	=>	"Initials",
				"creatorFirstName"	=>	"First name",
				"creatorFirstNameFull"	=>	"Full",
				"creatorFirstNameInitials"	=>	"Initial",
				"primaryCreatorList"	=>	"Primary creator list abbreviation",
				"otherCreatorList"	=>	"Other creator list abbreviation",
				"creatorListFull"	=>	"Full list",
				"creatorListLimit"	=>	"Limit list",
// The next 3 surround form text boxes:
// "If xx or more creators, list the first xx and abbreviate with xx".  For example:
// "If 4 or more creators, list the first 1 and abbreviate with ,et. al"
				"creatorListIf"		=>	"If",
				"creatorListOrMore"		=>	"or more creators, list the first",
				"creatorListAbbreviation"	=>	"and abbreviate with",
				"titleCapitalization"	=>	"Title capitalization",
// Title as entered with no changes to capitalization
				"titleAsEntered"	=>	"As entered",
				"availableFields"	=>	"Available fields:",
				"editionFormat"		=>	"Edition format",
				"monthFormat"		=>	"Month format",
				"dateFormat"		=>	"Date format",
				"dayFormat"		=>	"Day format",
				"pageFormat"		=>	"Page format",
// Length of film, broadcast etc.
				"runningTimeFormat"	=>	"Running time format",
// When displaying a book that has no author but has an editor, do we put the editor in the position occupied 
// by the author?
				"editorSwitchHead"		=>	"Editor switch",
				"editorSwitch"		=>	"For books with no author but an editor, put editor in author position",
				"yes"			=>	"Yes",
				"no"			=>	"No",
				"editorSwitchIfYes"	=>	"If 'Yes', replace editor field in style definitions with",
// Uppercase creator names?
				"uppercaseCreator"	=>	"Uppercase all names",
// For repeated creator names in next bibliographic item
				"repeatCreators"	=>	"For works immediately following by the same creators",
				"repeatCreators1"	=>	"Print the creator list",
				"repeatCreators2"	=>	"Do not print the creator list",
				"repeatCreators3"	=>	"Replace creator list with text below",
// Fallback formatting style when a specific resource type has none defined
				"fallback"		=>	"Fallback style",
				"bibFormat"		=>	"Bibliography Formatting",
// Italic font
				"italics"		=>	"Italics",
// For user specific month naming
				"userMonthSelect"	=>	"Use month names defined below",
				"userMonths"	=>	"User-defined month names (all fields must be completed if selected above)",
// Date ranges for e.g. conferences
				"dateRange"		=>	"Date range",
				"dateRangeDelimit1"	=>	"Delimiter between start and end dates if day and month given",
				"dateRangeDelimit2"	=>	"Delimiter between start and end dates if month only given",
				"dateRangeSameMonth"	=>	"If start and end months are equal",
				"dateRangeSameMonth1"	=>	"Print both months",
				"dateRangeSameMonth2"	=>	"Print start month only",
			),
		"creators" => array(
				"author"	=>	"Authors",
				"editor"	=>	"Editors",
				"translator"	=>	"Translators",
				"reviser"	=>	"Revisers",
				"seriesEditor"	=>	"Series Editors",
// For films etc.
				"director"	=>	"Director",
				"producer"	=>	"Producer",
// For artwork
				"artist"	=>	"Artist",
				"performer"	=>	"Performer",
// For legal cases
				"counsel"	=>	"Counsel",
// For classical works of doubtful provenance
				"attributedTo"	=>	"Attributed to",
// Map makers
				"cartographer"	=>	"Cartographer",
// Charts/images
				"creator"	=>	"Creator",
// For patents
				"inventor"	=>	"Inventor",
				"issuingOrganisation"	=>	"Issuing Organisation",
				"agent"		=>	"Agent/Attorney",
// International patent author
				"intAuthor"	=>	"International Author",
// Personal Communication
				"recipient"	=>	"Recipient",
// For Musical works
				"composer"	=>	"Composer",
				"conductor"	=>	"Conductor",
// Advice on what to do when editing a creator name and the new name already exists in the database.
				"creatorExists"	=>	"If you proceed, this edited creator will be deleted and all references in the database to it will be replaced by references to the pre-existing creator.",
			),
		);
	}
}
?>
