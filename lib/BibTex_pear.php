<?php
  /**
   * The following changes have been made by Raphael Reitzig, 2010:
   * - fixed spelling (l184)
   * - added source bibtex to entry in data array (l380)
   * - added entry key to entry in data array if present (l394 ff)
   * - fixed brace removal (l893)
   */

  /**
   * Class for working with BibTex data
   *
   * A class which provides common methods to access and
   * create Strings in BibTex format
   *
   * PHP versions 4 and 5
   *
   * LICENSE: This source file is subject to version 3.0 of the PHP license
   * that is available through the world-wide-web at the following URI:
   * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
   * the PHP License and are unable to obtain it through the web, please
   * send a note to license@php.net so we can mail you a copy immediately.
   *
   * @category   Structures
   * @package    PaperciteStructures_BibTex
   * @author     Elmar Pitschke <elmar.pitschke@gmx.de>
   * @copyright  1997-2005 The PHP Group
   * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
   * @version    CVS: $Id: BibTex.php 304756 2010-10-25 10:19:43Z clockwerx $
   * @link       http://pear.php.net/package/PaperciteStructures_BibTex
   */

require_once 'PEAR.php' ;
require_once 'bibtex_common.php';


/**
 * PaperciteStructures_BibTex
 *
 * A class which provides common methods to access and
 * create Strings in BibTex format.
 * Example 1: Parsing a BibTex File and returning the number of entries
 * <code>
 * $bibtex = new PaperciteStructures_BibTex();
 * $ret    = $bibtex->loadFile('foo.bib');
 * if (PEAR::isError($ret)) {
 *   die($ret->getMessage());
 * }
 * $bibtex->parse();
 * print "There are ".$bibtex->amount()." entries";
 * </code>
 * Example 2: Parsing a BibTex File and getting all Titles
 * <code>
 * $bibtex = new PaperciteStructures_BibTex();
 * $ret    = $bibtex->loadFile('bibtex.bib');
 * if (PEAR::isError($ret)) {
 *   die($ret->getMessage());
 * }
 * $bibtex->parse();
 * foreach ($bibtex->data as $entry) {
 *  print $entry['title']."<br />";
 * }
 * </code>
 * Example 3: Adding an entry and printing it in BibTex Format
 * <code>
 * $bibtex                         = new PaperciteStructures_BibTex();
 * $addarray                       = array();
 * $addarray['entrytype']          = 'Article';
 * $addarray['cite']               = 'art2';
 * $addarray['title']              = 'Titel2';
 * $addarray['author'][0]['first'] = 'John';
 * $addarray['author'][0]['last']  = 'Doe';
 * $addarray['author'][1]['first'] = 'Jane';
 * $addarray['author'][1]['last']  = 'Doe';
 * $bibtex->addEntry($addarray);
 * print nl2br($bibtex->bibTex());
 * </code>
 *
 * @category   Structures
 * @package    PaperciteStructures_BibTex
 * @author     Elmar Pitschke <elmar.pitschke@gmx.de>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/Structures/Structure_BibTex
 */
class PaperciteStructures_BibTex
{
    /**
     * Array with the BibTex Data
     *
     * @access public
     * @var array
     */
    var $data;
    /**
     * String with the BibTex content
     *
     * @access public
     * @var string
     */
    var $content;
    /**
     * Array with possible Delimiters for the entries
     *
     * @access private
     * @var array
     */
    var $_delimiters;
    /**
     * Array to store warnings
     *
     * @access public
     * @var array
     */
    var $warnings;
    /**
     * Run-time configuration options
     *
     * @access private
     * @var array
     */
    var $_options;
    /**
     * RTF Format String
     *
     * @access public
     * @var string
     */
    var $rtfstring;
    /**
     * HTML Format String
     *
     * @access public
     * @var string
     */
    var $htmlstring;
    /**
     * Array with the "allowed" entry types
     *
     * @access public
     * @var array
     */
    var $allowedEntryTypes;
    /**
     * Author Format Strings
     *
     * @access public
     * @var string
     */
    var $authorstring;

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    function __construct($options = array())
    {
        $this->_delimiters     = array('"'=>'"',
                                        '{'=>'}');
        $this->data            = array();
        $this->content         = '';
        //$this->_stripDelimiter = $stripDel;
        //$this->_validate       = $val;
        $this->warnings        = array();
        $this->_options        = array(
            'stripDelimiter'    => true,
            'validate'          => true,
            'unwrap'            => false,
            'wordWrapWidth'     => false,
            'wordWrapBreak'     => "\n",
            'wordWrapCut'       => 0,
            'removeCurlyBraces' => false,
            'extractAuthors'    => true,
            'processTitles'     => true
        );
        foreach ($options as $option => $value) {
            $test = $this->setOption($option, $value);
            if (PEAR::isError($test)) {
                //Currently nothing is done here, but it could for example raise an warning
            }
        }
        $this->allowedEntryTypes = array(
            'article',
            'book',
            'booklet',
            'conference',
            'inbook',
            'incollection',
            'inproceedings',
            'manual',
            'mastersthesis',
            'misc',
            'phdthesis',
            'proceedings',
            'techreport',
            'unpublished'
        );
        $this->authorstring = 'VON LAST, JR, FIRST';
    }

    /**
     * Sets run-time configuration options
     *
     * @access public
     * @param string $option option name
     * @param mixed  $value value for the option
     * @return mixed true on success PEAR_Error on failure
     */
    function setOption($option, $value)
    {
        $ret = true;
        if (array_key_exists($option, $this->_options)) {
            $this->_options[$option] = $value;
        } else {
            $ret = PEAR::raiseError('Unknown option '.$option);
        }
        return $ret;
    }

    /**
     * Reads a given BibTex File
     *
     * @access public
     * @param string $filename Name of the file
     * @return mixed true on success PEAR_Error on failure
     */
    function loadFile($filename)
    {
        if (file_exists($filename)) {
            if (($this->content = @file_get_contents($filename)) === false) {
                return PEAR::raiseError('Could not open file '.$filename);
            } else {
                $this->_pos    = 0;
                $this->_oldpos = 0;
                return true;
            }
        } else {
            return PEAR::raiseError('Could not find file '.$filename);
        }
    }

    /**
     * Reads bibtex from a string variable
     *
     * @access public
     * @param string $bib String containing bibtex
     * @return boolean true
     */
    function loadString($bib)
    {
        $this->content = $bib;
        $this->_pos    = 0;
        $this->_oldpos = 0;
        return true; // For compatibility with loadFile
    }
    
    /**
     * Parses what is stored in content and clears the content if the parsing is successfull.
     *
     * @access public
     * @return boolean true on success and PEAR_Error if there was a problem
     */
    function parse()
    {
        //The amount of opening braces is compared to the amount of closing braces
        //Braces inside comments are ignored
        $this->warnings = array();
        $this->data     = array();
        $valid          = true;
        $open           = 0;
        $entry          = false;
        $char           = '';
        $lastchar       = '';
        $buffer         = '';
        for ($i = 0; $i < strlen($this->content); $i++) {
            $char = substr($this->content, $i, 1);
            if ((0 != $open) && ('@' == $char)) {
                if (!$this->_checkAt($buffer)) {
                    $this->_generateWarning('WARNING_MISSING_END_BRACE', '', $buffer);
                    //To correct the data we need to insert a closing brace
                    $char     = '}';
                    $i--;
                }
            }
            if ((0 == $open) && ('@' == $char)) { //The beginning of an entry
                $entry = true;
            } elseif ($entry && ('{' == $char) && ('\\' != $lastchar)) { //Inside an entry and non quoted brace is opening
                $open++;
            } elseif ($entry && ('}' == $char) && ('\\' != $lastchar)) { //Inside an entry and non quoted brace is closing
                $open--;
                if ($open < 0) { //More are closed than opened
                    $valid = false;
                }
                if (0 == $open) { //End of entry
                    $entry     = false;
                    $entrydata = $this->_parseEntry($buffer);
                    if (!$entrydata) {
                        /**
                         * This is not yet used.
                         * We are here if the Entry is either not correct or not supported.
                         * But this should already generate a warning.
                         * Therefore it should not be necessary to do anything here
                         */
                    } else {
                        $this->data[] = $entrydata;
                    }
                    $buffer = '';
                }
            }
            if ($entry) { //Inside entry
                $buffer .= $char;
            }
            $lastchar = $char;
        }
        //If open is one it may be possible that the last ending brace is missing
        if (1 == $open) {
            $entrydata = $this->_parseEntry($buffer);
            if (!$entrydata) {
                $valid = false;
            } else {
                $this->data[] = $entrydata;
                $buffer = '';
                $open   = 0;
            }
        }
        //At this point the open should be zero
        if (0 != $open) {
            $valid = false;
        }
        //Are there Multiple entries with the same cite?
        if ($this->_options['validate']) {
            $cites = array();
            foreach ($this->data as $entry) {
                if (isset($entry['cite'])) {
                    $cites[] = $entry['cite'];
                }
            }
            $unique = array_unique($cites);
            if (sizeof($cites) != sizeof($unique)) { //Some values have not been unique!
                $notuniques = array();
                for ($i = 0; $i < sizeof($cites); $i++) {
                    if ('' == $unique[$i]) {
                        $notuniques[] = $cites[$i];
                    }
                }
                $this->_generateWarning('WARNING_MULTIPLE_ENTRIES', implode(',', $notuniques));
            }
        }


        if ($valid) {
            $this->content = '';
            return true;
        } else {
            return PEAR::raiseError('Unbalanced parenthesis');
        }
    }

    static function process_accents(&$text)
    {
        $text = preg_replace_callback("#\\\\(?:['\"^`H~\.]|¨)\w|\\\\([LlcCoO]|ss|aa|AA|[ao]e|[OA]E|&)#", "PaperciteStructures_BibTex::_accents_cb", $text);
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

    static function _accents_cb($input)
    {
        if (!array_key_exists($input[0], PaperciteStructures_BibTex::$accents)) {
            return "$input[0]";
        }
        return  PaperciteStructures_BibTex::$accents[$input[0]];
    }

    /**
     * Extracting the data of one content
     *
     * The parse function splits the content into its entries.
     * Then every entry is parsed by this function.
     * It parses the entry backwards.
     * First the last '=' is searched and the value extracted from that.
     * A copy is made of the entry if warnings should be generated. This takes quite
     * some memory but it is needed to get good warnings. If nor warnings are generated
     * then you don have to worry about memory.
     * Then the last ',' is searched and the field extracted from that.
     * Again the entry is shortened.
     * Finally after all field=>value pairs the cite and type is extraced and the
     * authors are splitted.
     * If there is a problem false is returned.
     *
     * @access private
     * @param string $entry The entry
     * @return array The representation of the entry or false if there is a problem
     */
    function _parseEntry($entry)
    {
        $entrycopy = '';
        if ($this->_options['validate']) {
            $entrycopy = $entry; //We need a copy for printing the warnings
        }
        $ret = array('bibtex' => $entry.'}');
        if ('@string' ==  strtolower(substr($entry, 0, 7))) {
            //String are not yet supported!
            if ($this->_options['validate']) {
                $this->_generateWarning('STRING_ENTRY_NOT_YET_SUPPORTED', '', $entry.'}');
            }
        } elseif ('@preamble' ==  strtolower(substr($entry, 0, 9))) {
            //Preamble not yet supported!
            if ($this->_options['validate']) {
                $this->_generateWarning('PREAMBLE_ENTRY_NOT_YET_SUPPORTED', '', $entry.'}');
            }
        } elseif ('@comment' ==  strtolower(substr($entry, 0, 8))) {
      // Just ignores
        } else {
            // Look for key
            $matches = array();
            preg_match('/^@\w+\{([\w\d]+),/', $entry, $matches);
            if (count($matches) > 0) {
                $ret['entrykey'] = $matches[1];
            }

            //Parsing all fields
            while (strrpos($entry, '=') !== false) {
                $position = strrpos($entry, '=');
                //Checking that the equal sign is not quoted or is not inside a equation (For example in an abstract)
                $proceed  = true;
                if (substr($entry, $position-1, 1) == '\\') {
                    $proceed = false;
                }
                if ($proceed) {
                    $proceed = $this->_checkEqualSign($entry, $position);
                }
                while (!$proceed) {
                    $substring = substr($entry, 0, $position);
                    $position  = strrpos($substring, '=');
                    $proceed   = true;
                    if (substr($entry, $position-1, 1) == '\\') {
                        $proceed = false;
                    }
                    if ($proceed) {
                        $proceed = $this->_checkEqualSign($entry, $position);
                    }
                }

                $value = trim(substr($entry, $position+1));
                $entry = substr($entry, 0, $position);

                if (',' == substr($value, strlen($value)-1, 1)) {
                    $value = substr($value, 0, -1);
                }
                if ($this->_options['validate']) {
                    $this->_validateValue($value, $entrycopy);
                }
                if ($this->_options['stripDelimiter']) {
                    $value = $this->_stripDelimiter($value);
                }
                if ($this->_options['unwrap']) {
                    $value = $this->_unwrap($value);
                }
                if ($this->_options['removeCurlyBraces']) {
                    $value = $this->_removeCurlyBraces($value);
                }
                $position    = strrpos($entry, ',');
                $field       = strtolower(trim(substr($entry, $position+1)));
                $ret[$field] = $value;
                $entry       = substr($entry, 0, $position);
            }
            //Parsing cite and entry type
            $arr = explode('{', $entry);
            $ret['cite'] = trim($arr[1]);
            $ret['entrytype'] = strtolower(trim($arr[0]));
            if ('@' == $ret['entrytype']{0}) {
                $ret['entrytype'] = substr($ret['entrytype'], 1);
            }
            if ($this->_options['validate']) {
                if (!$this->_checkAllowedEntryType($ret['entrytype'])) {
                    $this->_generateWarning('WARNING_NOT_ALLOWED_ENTRY_TYPE', $ret['entrytype'], $entry.'}');
                }
            }

            // Process accents
            foreach ($ret as $key => &$value) {
                if ($key != "bibtex") {
                      PaperciteStructures_BibTex::process_accents($value);
                }
            }
        
            // Handling pages
            if (in_array('pages', array_keys($ret))) {
                $matches = array();
                if (preg_match("/^\s*(\d+)(?:\s*--?\s*(\d+))?\s*$/", $ret['pages'], $matches)) {
                    if (count($matches) > 2) {
                        $ret['pages'] = new PaperciteBibtexPages($matches[1], $matches[2]);
                    }
                }
            }

            //Handling the authors
            if (in_array('author', array_keys($ret)) && $this->_options['extractAuthors']) {
                $ret['author'] = $this->_extractAuthors($ret['author']);
            }
            //Handling the editors
            if (in_array('editor', array_keys($ret)) && $this->_options['extractAuthors']) {
                $ret['editor'] = $this->_extractAuthors($ret['editor']);
            }
        }
        return $ret;
    }

    /**
     * Checking whether the position of the '=' is correct
     *
     * Sometimes there is a problem if a '=' is used inside an entry (for example abstract).
     * This method checks if the '=' is outside braces then the '=' is correct and true is returned.
     * If the '=' is inside braces it contains to a equation and therefore false is returned.
     *
     * @access private
     * @param string $entry The text of the whole remaining entry
     * @param int the current used place of the '='
     * @return bool true if the '=' is correct, false if it contains to an equation
     */
    function _checkEqualSign($entry, $position)
    {
        $ret = true;
        //This is getting tricky
        //We check the string backwards until the position and count the closing an opening braces
        //If we reach the position the amount of opening and closing braces should be equal
        $length = strlen($entry);
        $open   = 0;
        for ($i = $length-1; $i >= $position; $i--) {
            $precedingchar = substr($entry, $i-1, 1);
            $char          = substr($entry, $i, 1);
            if (('{' == $char) && ('\\' != $precedingchar)) {
                $open++;
            }
            if (('}' == $char) && ('\\' != $precedingchar)) {
                $open--;
            }
        }
        if (0 != $open) {
            $ret = false;
        }
        //There is still the posibility that the entry is delimited by double quotes.
        //Then it is possible that the braces are equal even if the '=' is in an equation.
        if ($ret) {
            $entrycopy = trim($entry);
            $lastchar  = $entrycopy{strlen($entrycopy)-1};
            if (',' == $lastchar) {
                $lastchar = $entrycopy{strlen($entrycopy)-2};
            }
            if ('"' == $lastchar) {
                //The return value is set to false
                //If we find the closing " before the '=' it is set to true again.
                //Remember we begin to search the entry backwards so the " has to show up twice - ending and beginning delimiter
                $ret = false;
                $found = 0;
                for ($i = $length; $i >= $position; $i--) {
                    $precedingchar = substr($entry, $i-1, 1);
                    $char          = substr($entry, $i, 1);
                    if (('"' == $char) && ('\\' != $precedingchar)) {
                        $found++;
                    }
                    if (2 == $found) {
                        $ret = true;
                        break;
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Checking if the entry type is allowed
     *
     * @access private
     * @param string $entry The entry to check
     * @return bool true if allowed, false otherwise
     */
    function _checkAllowedEntryType($entry)
    {
        return in_array($entry, $this->allowedEntryTypes);
    }

    /**
     * Checking whether an at is outside an entry
     *
     * Sometimes an entry misses an entry brace. Then the at of the next entry seems to be
     * inside an entry. This is checked here. When it is most likely that the at is an opening
     * at of the next entry this method returns true.
     *
     * @access private
     * @param string $entry The text of the entry until the at
     * @return bool true if the at is correct, false if the at is likely to begin the next entry.
     */
    function _checkAt($entry)
    {
        $ret     = false;
        $opening = array_keys($this->_delimiters);
        $closing = array_values($this->_delimiters);
        //Getting the value (at is only allowd in values)
        if (strrpos($entry, '=') !== false) {
            $position = strrpos($entry, '=');
            $proceed  = true;
            if (substr($entry, $position-1, 1) == '\\') {
                $proceed = false;
            }
            while (!$proceed) {
                $substring = substr($entry, 0, $position);
                $position  = strrpos($substring, '=');
                $proceed   = true;
                if (substr($entry, $position-1, 1) == '\\') {
                    $proceed = false;
                }
            }
            $value    = trim(substr($entry, $position+1));
            $open     = 0;
            $char     = '';
            $lastchar = '';
            for ($i = 0; $i < strlen($value); $i++) {
                $char = substr($this->content, $i, 1);
                if (in_array($char, $opening) && ('\\' != $lastchar)) {
                    $open++;
                } elseif (in_array($char, $closing) && ('\\' != $lastchar)) {
                    $open--;
                }
                $lastchar = $char;
            }
            //if open is grater zero were are inside an entry
            if ($open>0) {
                $ret = true;
            }
        }
        return $ret;
    }

    /**
     * Stripping Delimiter
     *
     * @access private
     * @param string $entry The entry where the Delimiter should be stripped from
     * @return string Stripped entry
     */
    function _stripDelimiter($entry)
    {
        $beginningdels = array_keys($this->_delimiters);
        $length        = strlen($entry);
        $firstchar     = substr($entry, 0, 1);
        $lastchar      = substr($entry, -1, 1);
        while (in_array($firstchar, $beginningdels)) { //The first character is an opening delimiter
            if ($lastchar == $this->_delimiters[$firstchar]) { //Matches to closing Delimiter
                $entry = substr($entry, 1, -1);
            } else {
                break;
            }
            $firstchar = substr($entry, 0, 1);
            $lastchar  = substr($entry, -1, 1);
        }
        return $entry;
    }

    /**
     * Unwrapping entry
     *
     * @access private
     * @param string $entry The entry to unwrap
     * @return string unwrapped entry
     */
    function _unwrap($entry)
    {
        $entry = preg_replace('/\s+/', ' ', $entry);
        return trim($entry);
    }

    /**
     * Wordwrap an entry
     *
     * @access private
     * @param string $entry The entry to wrap
     * @return string wrapped entry
     */
    function _wordwrap($entry)
    {
        if ((''!=$entry) && (is_string($entry))) {
            $entry = wordwrap($entry, $this->_options['wordWrapWidth'], $this->_options['wordWrapBreak'], $this->_options['wordWrapCut']);
        }
        return $entry;
    }

    /**
     * Extracting the authors
     *
     * @access private
     * @param string $entry The entry with the authors
     * @return array the extracted authors
     */
    function _extractAuthors($authors)
    {
        return PaperciteBibtexCreators::parse($authors);
    }

    /**
     * Case Determination according to the needs of BibTex
     *
     * To parse the Author(s) correctly a determination is needed
     * to get the Case of a word. There are three possible values:
     * - Upper Case (return value 1)
     * - Lower Case (return value 0)
     * - Caseless   (return value -1)
     *
     * @access private
     * @param string $word
     * @return int The Case or PEAR_Error if there was a problem
     */
    function _determineCase($word)
    {
        $ret         = -1;
        $trimmedword = trim($word);
        /*We need this variable. Without the next of would not work
         (trim changes the variable automatically to a string!)*/
        if (is_string($word) && (strlen($trimmedword) > 0)) {
            $i         = 0;
            $found     = false;
            $openbrace = 0;
            while (!$found && ($i <= strlen($word))) {
                $letter = substr($trimmedword, $i, 1);
                $ord    = ord($letter);
                if ($ord == 123) { //Open brace
                    $openbrace++;
                }
                if ($ord == 125) { //Closing brace
                    $openbrace--;
                }
                if (($ord>=65) && ($ord<=90) && (0==$openbrace)) { //The first character is uppercase
                    $ret   = 1;
                    $found = true;
                } elseif (($ord>=97) && ($ord<=122) && (0==$openbrace)) { //The first character is lowercase
                    $ret   = 0;
                    $found = true;
                } else { //Not yet found
                    $i++;
                }
            }
        } else {
            $ret = PEAR::raiseError('Could not determine case on word: '.(string)$word);
        }
        return $ret;
    }

    /**
     * Validation of a value
     *
     * There may be several problems with the value of a field.
     * These problems exist but do not break the parsing.
     * If a problem is detected a warning is appended to the array warnings.
     *
     * @access private
     * @param string $entry The entry aka one line which which should be validated
     * @param string $wholeentry The whole BibTex Entry which the one line is part of
     * @return void
     */
    function _validateValue($entry, $wholeentry)
    {
        //There is no @ allowed if the entry is enclosed by braces
        if (preg_match('/^{.*@.*}$/', $entry)) {
            $this->_generateWarning('WARNING_AT_IN_BRACES', $entry, $wholeentry);
        }
        //No escaped " allowed if the entry is enclosed by double quotes
        if (preg_match('/^\".*\\".*\"$/', $entry)) {
            $this->_generateWarning('WARNING_ESCAPED_DOUBLE_QUOTE_INSIDE_DOUBLE_QUOTES', $entry, $wholeentry);
        }
        //Amount of Braces is not correct
        $open     = 0;
        $lastchar = '';
        $char     = '';
        for ($i = 0; $i < strlen($entry); $i++) {
            $char = substr($entry, $i, 1);
            if (('{' == $char) && ('\\' != $lastchar)) {
                $open++;
            }
            if (('}' == $char) && ('\\' != $lastchar)) {
                $open--;
            }
            $lastchar = $char;
        }
        if (0 != $open) {
            $this->_generateWarning('WARNING_UNBALANCED_AMOUNT_OF_BRACES', $entry, $wholeentry);
        }
    }

    /**
     * Remove curly braces from entry
     *
     * @access private
     * @param string $value The value in which curly braces to be removed
     * @param string Value with removed curly braces
     */
    function _removeCurlyBraces($value)
    {
        //First we save the delimiters
        $beginningdels = array_keys($this->_delimiters);
        $firstchar     = substr($value, 0, 1);
        $lastchar      = substr($value, -1, 1);
        $begin         = '';
        $end           = '';
        while (in_array($firstchar, $beginningdels)) { //The first character is an opening delimiter
            if ($lastchar == $this->_delimiters[$firstchar]) { //Matches to closing Delimiter
                $begin .= $firstchar;
                $end   .= $lastchar;
                $value  = substr($value, 1, -1);
            } else {
                break;
            }
            $firstchar = substr($value, 0, 1);
            $lastchar  = substr($value, -1, 1);
        }
        //Now we get rid of the curly braces
        $value = preg_replace('/[\{\}]/', '', $value);
        //Reattach delimiters
        $value       = $begin.$value.$end;
        return $value;
    }

    /**
     * Generates a warning
     *
     * @access private
     * @param string $type The type of the warning
     * @param string $entry The line of the entry where the warning occurred
     * @param string $wholeentry OPTIONAL The whole entry where the warning occurred
     */
    function _generateWarning($type, $entry, $wholeentry = '')
    {
        $warning['warning']    = $type;
        $warning['entry']      = $entry;
        $warning['wholeentry'] = $wholeentry;
        $this->warnings[]      = $warning;
    }

    /**
     * Cleares all warnings
     *
     * @access public
     */
    function clearWarnings()
    {
        $this->warnings = array();
    }

    /**
     * Is there a warning?
     *
     * @access public
     * @return true if there is, false otherwise
     */
    function hasWarning()
    {
        if (sizeof($this->warnings)>0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the amount of available BibTex entries
     *
     * @access public
     * @return int The amount of available BibTex entries
     */
    function amount()
    {
        return sizeof($this->data);
    }

   

    /**
     * Converts the stored BibTex entries to a BibTex String
     *
     * In the field list, the author is the last field.
     *
     * @access public
     * @return string The BibTex string
     */
    function bibTex()
    {
        $bibtex = '';
        foreach ($this->data as $entry) {
            //Intro
            $bibtex .= '@'.strtolower($entry['entrytype']).' { '.$entry['cite'].",\n";
            //Other fields except author
            foreach ($entry as $key => $val) {
                if ($this->_options['wordWrapWidth']>0) {
                    $val = $this->_wordWrap($val);
                }
                if (!in_array($key, array('cite','entrytype','author'))) {
                    $bibtex .= "\t".$key.' = {'.$val."},\n";
                }
            }
            //Author
            if (array_key_exists('author', $entry)) {
                if ($this->_options['extractAuthors']) {
                    $tmparray = array(); //In this array the authors are saved and the joind with an and
                    foreach ($entry['author'] as $authorentry) {
                        $tmparray[] = $this->_formatAuthor($authorentry);
                    }
                    $author = join(' and ', $tmparray);
                } else {
                    $author = $entry['author'];
                }
            } else {
                $author = '';
            }
            $bibtex .= "\tauthor = {".$author."}\n";
            $bibtex.="}\n\n";
        }
        return $bibtex;
    }
}
