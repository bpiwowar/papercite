OSBib-Format
A collection of PHP classes to manage bibliographic formatting for OS bibliography software 
using the OSBib standard.  Taken from WIKINDX (http://wikindx.sourceforge.net).

Released through http://bibliophile.sourceforge.net under the GPL licence.

If you make improvements, please consider contacting the administrators at bibliophile.sourceforge.net 
so that your improvements can be added to the release package.

May 2005
Mark Grimshaw (http://wikindx.sourceforge.net)
Andrea Rossato (http://uniwakka.sourceforge.net/HomePage)
Guillaume Gardey (http://biborb.glymn.net/doku.php) 

OSBib is an Open Source bibliographic formatting engine written in PHP that use XML style files to store formatting data for in-text citations/footnotes and bibliographic lists. Released through Bibliophile, OSBib is designed to work with bibliographic data stored in any format via mapping arrays as defined in the class STYLEMAP. For those bibliographic systems whose data is stored in or that can be accessed as bibtex-type arrays, STYLEMAPBIBTEX is a set of pre-defined mapping arrays designed to get you up and running within a matter of minutes. Data stored in other formats require that STYLEMAP be edited. 

Style files are stored in XML format and are available for download from the Bibliophile site at:
http://bibliophile.sourceforge.net
The naming of the style files to be downloaded is (for example):
OSBib-americanPsychologicalAssociation_1.0_1.1
where the first number (in this case '1.0') is the version number of the OSBib classes the style is at least compatible with and the second number is the version number of the style file itself.

Please note.  Although in-text/footnotes citation formatting is defined in the XML style files, this version of OSBib-Format does not yet have the citation engine and will only handle bibliographic lists.  Citation formatting will come...

The OSBib package does not yet have classes to handle the creation and editing of XML style files.  This too will come...

*************************************************************************************
USAGE:
*************************************************************************************

BIBSTYLE.php
This is not part of the distribution but is here as an example of how WIKINDX uses OSBib-Format.  process() is the loop that parses each bibliographic entry one by one.  You are likely to need a similar process loop.

			***************************************

PARSEXML.php
Parse the XML style file into usable arrays.  Used within BIBFORMAT::loadStyle().  See BIBFORMAT.php below.

LOADSTYLE.php
include_once($pathToOsbibClasses . "LOADSTYLE.php");
ARRAY LOADSTYLE::loadDir($pathToStyleFileDirectory);
This scans the style file directory and returns an alphabetically sorted (on the key) array of available bibliographic styles e.g.
$styles = LOADSTYLE::loadDir("styles/bibliography");
print_r($styles);

This would output:
Array ( [APA] => American Psychological Association (APA) [BRITISHMEDICALJOURNAL] => British Medical Journal (BMJ) [CHICAGO] => Chicago [HARVARD] => Harvard [IEEE] => Institute of Electrical and Electronic Engineers (IEEE) [MLA] => Modern Language Association (MLA) [TEST] => test [TURABIAN] => Turabian [WIKINDX] => WIKINDX -- Show All )

Use this to provide your users with a HTML FORM selectbox to choose their preferred style where the key from the array above is used in BIBFORMAT::loadStyle() (see below).

			***************************************

PARSESTYLE.php
This is used internally in BIBFORMAT.php and currently just parses a single style definition string for a particular resource type (book, web article etc.) from a style XML file into an array to be used by OSBib.

			***************************************

STYLEMAP.php
(If your database stores or access its records in a BibTeX style format, you should use STYLEMAPBIBTEX.php instead as this has been specially devised to offer an out-of-the-box solution for such systems and is a version of STYLEMAP that should not require editing. See also GENERAL USAGE below.)
This contains all the mapping between your particular database/bibliographic management system and OSBib.  There are plenty of comments in that file so read them carefully.
1/ You should edit $this->types.
2/ You should edit each resource type's array changing ONLY the key of each element.  However, do NOT edit any key (or its value) that is 'creator1', 'creator2', 'creator3', 'creator4' or 'creator5'.  For resource types in $this->types that you set to FALSE, you do not need to do anything to the specific resource array as these arrays will be ignored.

A SQL query in WIKINDX to display each resource in a format suitable for OSBib processing returns the following associative array for each resource:
Array ( [resourceId] => 1 [type] => journal_article [title] => {X} Window System, Version 11 [subtitle] => [noSort] => The [url] => [isbn] => [field1] => 20 [field2] => S2 [field3] => [field4] => [field5] => [field6] => [field7] => [field8] => [field9] => [file] => [collection] => 1 [publisher] => [miscField1] => [miscField2] => [miscField3] => [miscField4] => [tag] => [addUserIdResource] => 1 [editUserIdResource] => [year1] => 1990 [year2] => [year3] => [pageStart] => [pageEnd] => [creator1] => 1,2,3 [creator2] => [creator3] => [creator4] => [creator5] => [quotes] => [paraphrases] => [musings] => [publisherName] => [publisherLocation] => [publisherType] => [collectionTitle] => Software Practice and Experience [collectionTitleShort] => [collectionType] => journal [timestamp] => 2005-04-24 10:48:15 )

What is important here is that the key names of the above array match the key names of the resource type arrays in STYLEMAP.php.  This is how the data from _your_ particular database is mapped to a format that OSBib understands and this is why you MUST the edit the key names of the resource type array in STYLEMAP.php.  The ONE exception to this is the handling of creator elements (author, editor, composer, inventor etc.) which OSBib expects to be listed as 'creator1', 'creator2', 'creator3', 'creator4' and 'creator5' where 'creator1' is always the PRIMARY creator (usually the author).  Do NOT edit these key names.

			***************************************
UTF8
include_once($pathToOsbibClasses . "BIBFORMAT.php"); 
$utf8 = new UTF8();

BIBFORMAT expects its data to be in UTF-8 format and will return its formatted data in UTF-8 format. If you need to encode or decode your data prior to or after using OSBib, do not use PHP's utf8_encode() and utf8_decode() functions. Use the OSBib functions UTF8::encodeUtf8() and UTF8::decodeUtf8() instead. Additionally, if you need to manipulate UTF-8-encoded strings with functions such as strtolower(), strlen() etc., you should strongly consider using the appropriate methods in the OSBib UTF8 class.

METHODS:
UTF8::encodeUtf8()
$utf8String = $utf8->encodeUtf8(STRING: $string);
Properly encode a string into multi-byte UTF-8. 

UTF8::decodeUtf8()
$string = $utf8->decodeUtf8(STRING: $utf8String);
Properly decode a multi-byte UTF-8 string. 

UTF8::utf8_strtolower()
$utf8String = $utf8->utf8_strtolower(STRING: $utf8String);
Convert a UTF-8 string to lowercase. Where PHP has been compiled with mb_string, mb_strtolower() will be used. 

UTF8::utf8_strtoupper()
$utf8String = $utf8->utf8_strtoupper(STRING: $utf8String);
Convert a UTF-8 string to uppercase. Where PHP has been compiled with mb_string, mb_strtoupper() will be used. 

UTF8::utf8_substr()
$utf8String = $utf8->utf8_strtolower(STRING: $utf8String, INT $start [, INT: $length=NULL]);
Return a portion of a UTF-8 string. Where PHP has been compiled with mb_string, mb_substr() will be used. 

UTF8::utf8_ucfirst()
$utf8String = $utf8->utf8_ucfirst(STRING: $utf8String);
Ensure that the first letter of a UTF-8 string is uppercase. 

UTF8::utf8_strlen()
$length = $utf8->utf8_strlen(STRING: $utf8String);
Return the length of a UTF-8 string. Where PHP has been compiled with mb_string, mb_strlen() will be used. 

			***************************************

BIBFORMAT.php
This is the main OSBib engine.
include_once("core/styles/BIBFORMAT.php");
$bibformat = new BIBFORMAT(STRING: $pathToOsbibClasses = FALSE [, BOOLEAN: $useBibtex = FALSE]);

By default, $pathToOsbibClasses will be the same directory as BIBFORMAT.php is in.

*****
NB -- BIBFORMAT expects its data to be in UTF-8 format and will return its formatted data in UTF-8 format. If you need to encode or decode your data prior to or after using OSBib, do not use PHP's utf8_encode() and utf8_decode() functions. Use the OSBib functions UTF8::encodeUtf8() and UTF8::decodeUtf8() instead. Additionally, if you need to manipulate UTF-8-encoded strings with functions such as strtolower(), strlen() etc., you should strongly consider using the appropriate methods in the OSBib UTF8 class.
*****

PROPERTIES to be set after instantiating the BIBFORMAT class:
$bibformat->output -- By default this property is 'html' but you can change it to 'rtf' for exporting to RTF files, 'sxw' for OpenOffice or 'plain' for plain text.  It is used to format bold, underline, italics etc. for the appropriate output medium.
$bibformat->patterns -- A preg pattern (e.g. "/matchThis|matchThat/i") that in conjunction with $bibformat->patternHighlight is used to highlight words or phrases when displaying the results to a browser.  This is useful when the bibliography to be displayed is the result of a SQL search.  Default is FALSE and its value will be ignored if $bibformat->output is anything other than 'html'.
$bibformat->patternHighlight -- A CSS class defining the highlighting for above.  Default is FALSE.
$bibformat->bibtexParsePath -- If you wish to use STYLEMAPBIBTEX.php because your database stores or accesses its data in a form similar to BibTeX, you should set the constructor parameter $useBibtex to TRUE and set this property to the path where PARSECREATORS, PARSEMONTH and PARSEPAGE can be found.  These classes are not part of OSBib but are part of the bibtexParse package that can be downloaded from http://bibliophile.sourceforge.net. By default, this path will be to a bibtexParse/ directory in the same directory as BIBFORMAT.php is in.

METHODS:
BIBFORMAT::loadStyle();
list($info, $citation, $styleCommon, $styleTypes) = $bibformat->loadStyle(STRING: $pathToStyleFiles, STRING: $styleFile);

Parses the XML style file into raw arrays (to be further processed in BIBFORMAT::getStyle() (see below).  The four associative arrays returned are:
$info -- general information about the resource including description, language, version etc.
$citation -- in-text citation styling (not currently used).
$styleCommon -- common styling for bibliographic output such as formatting of names, title capitalisation etc.
$styleTypes -- bibliographic styling for each resource type supported by that particular style.

These last two are used in BIBFORMAT::getStyle().

BIBFORMAT::getStyle();
$bibformat->getStyle(ASSOC_ARRAY: $styleCommon, ASSOC_ARRAY: $styleTypes);

Transform the raw XML arrays from BIBFORMAT::loadStyle() into OSBib-usable arrays and perform some pre-processing.

loadStyle() and getStyle() need be called only once.

The following should be called for each database row you wish to process.

BIBFORMAT::preProcess();
$row = $bibformat->preProcess(STRING: $type, ASSOC_ARRAY: $row);

$row is an associative array returned from your SQL query as described in the STYLEMAP.php section above.
$type is the resource type which must be one of the ones listed in $this->types in STYLEMAP.php.

Among other things, preProcess() supplies one of the three generic style definitions if the requested bibliographic style does not provide a definition for a specific resource type.  It also handles editor/author switching for books which have only editors.

Internally within BIBFORMAT.php, data from the SQL query $row is formatted and stored in a $item associative array.  The following methods accomplish this:

BIBFORMAT::formatNames()
This method should be called for each type of creator the resource has.  (See BIBSTYLE.php for an example of how this is used in WIKINDX.)
$bibformat->formatNames(ASSOC_ARRAY: $creators, STRING: $nameType);

$creators -- Multi-associative array of creator names. e.g. this array might be of the primary authors (in 'creator1'):
	array(
		[0] => array(['surname'] => 'Grimshaw', ['firstname'] => Mark, ['initials'] => 'N', ['prefix'] => ),
	   	[1] => array(['surname'] => 'Witt', ['firstname'] => Jan, ['initials'] => , ['prefix'] => 'de')
	);
$nameType -- One of 'creator1', 'creator2', 'creator3', 'creator4' or 'creator5'.  This is mapped against the resource type array in STYLEMAP.php to determine what type of creator we're looking at.  'creator1' is always assumed to be the primary creator whether that is an author, composer, inventor etc.

BIBFORMAT::formatTitle()
Format the title of the resource.
$bibformat->formatTitle(STRING: $title[, STRING: $delimitLeft, STRING: $delimitRight]);

$title -- The title of the resource.
$delimitLeft 
$delimitRight -- Some bibliographic styles require all except the first letter of the title to be lowercased.  If your bibliographic system allows users to specify a groups of letters in the title that should not be lowercased (for example, proper names), then you enter the delimiters here.  WIKINDX uses '{' and '}' as delimiters to protect character case.

BIBFORMAT::formatEdition()
$bibformat->formatEdition($edition);
Bibliographic styles may require the book edition number to be a cardinal or an ordinal number. If your edition number is stored in the database as a cardinal number, then it will be formatted as an ordinal number _if_ required by the bibliographic style. If your edition number is stored as anything other than a cardinal number it will be used unchanged. The conversion is English - i.e. '3' => '3rd'.  This works all the way up to infinity - 1 ;-)

BIBFORMAT::formatPages()
$bibformat->formatPages(STRING: $pageStart [, STRING: $pageEnd])

BIBFORMAT::formatDate()
$bibformat->formatDate(INT: $day, INT: $month);

BIBFORMAT::formatRunningTime()
$bibformat->formatRunningTime(INT: $minutes, INT: $hours);
Running time for films, broadcasts etc.

BIBFORMAT::addItem()
$bibformat->addItem(STRING: $item, STRING: $fieldName);
Add an item to the internal $item array in BIBFORMAT.php.  Use this to add elements of your resource to the $item array that do not require special formatting with the methods above.  If it's not added, it won't be displayed. You'll notice a use of this in the example BIBSTYLE.php for the URL of a resource.  If you don't need to do your own special formatting, it's far easier to use addAllOtherItems() below.

BIBFORMAT::addAllOtherItems()
$bibformat->addItem(ASSOC_ARRAY: $row);
Add all remaining items to the internal $item array in BIBFORMAT.php.  Use this to add elements of your resource to the $item array that do not require special formatting with the methods above.  If it's not added, it won't be displayed.

BIBFORMAT::map()
STRING $bibformat->map();
After you have added resource elements to the $item array using the methods above, calling map() will printing to the output medium.


*************************************************************************************
GENERAL USAGE and TIPS:
*************************************************************************************

The formatting in BIBFORMAT works on one resource at a time so you will want to call it via a loop as you cycle through your data.

If you do _not_ intend to use STYLEMAPBIBTEX.php, the following is a rough order of events within the loop described above and _after_ setting various properties following BIBFORMAT class instantiation.  It's a general outline of what happens in BIBSTYLE.php as used by WIKINDX:

// Get the resource type ('book', 'journal_article', 'artwork' etc.)
	$resourceType = $row['type'];
	$row = $bibformat->preProcess($resourceType, $databaseRow);
// PreProcessing may change the value of $resourceType so get it back!
	$resourceType = $bibformat->type;
// Add various resource elements to BIBFORMAT::item array that require special processing and formatting
1. Creator names
2. Resource title
3. Resource edition
4. Resource pages
5. Resource date
6. Resource running time
7. Add the URL creating a hyperlink for web browser display

// Add all the other elements of the resource to BIBFORMAT::item array
	$bibformat->addAllOtherItems($row);
// Finally, get the formatted resource string ready for printing to the web browser or exporting to RTF, 
// OpenOffice or plain text
	$string = $bibformat->map();


If you _are_ using STYLEMAPBIBTEX for reasons described in the sections above, then the following is a rough order of events within the loop described above and _after_ setting various properties following BIBFORMAT class instantiation:

// $resourceArray must be an array of all the elements in the resource where the key names are valid, lowercase BibTeX field names.  e.g.:
$resourceArray = array(
			'author'	=>	'Grimshaw, Mark and Boulanger, Christian',
			'title'		=>	'How Bibliographies Ruined our Lives',
			'year'		=>	'2005',
			'volume'	=>	'20',
			'number'	=>	'4',
			'journal'	=>	'Journal of Mundane Trivia',
			'pages'		=>	'42--111',
			'howpublished'	=>	"\url{http://bibliophile.sourceforge.net}",
		);

// Get the resource type ('book', 'article', 'inbook' etc.)
	$resourceType = 'misc';
// In this case, BIBFORMAT::preProcess() adds all the resource elements automatically to the BIBFORMAT::item array...
	$bibformat->preProcess($resourceType, $resourceArray);
// Finally, get the formatted resource string ready for printing to the web browser or exporting to RTF, 
// OpenOffice or plain text
	$string = $bibformat->map();