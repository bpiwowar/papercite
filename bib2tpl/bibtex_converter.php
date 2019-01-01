<?php
/*
 * By Raphael Reitzig, 2010
 * code@verrech.net
 * http://lmazy.verrech.net
 *
 * Modified by B. Piwowarski for inclusion in the papercite
 * WordPress plug-in:
 * - New template engine based on progressive parsing
 * - Two templates mode (one for the entries, one for the list)
 * - New macros
 *
 * This work is subject to Creative Commons
 * Attribution-NonCommercial-ShareAlike 3.0 Unported.
 * You are free:
 *     * to Share — to copy, distribute and transmit the work
 *     * to Remix — to adapt the work
 * Under the following conditions:
 *     * Attribution — You must attribute the work in the manner specified
 *       by the author or licensor (but not in any way that suggests that
 *       they endorse you or your use of the work).
 *     * Noncommercial — You may not use this work for commercial purposes.
 *     * Share Alike — If you alter, transform, or build upon this work,
 *       you may distribute the resulting work only under the same or similar
 *       license to this one.
 * With the understanding that:
 *     * Waiver — Any of the above conditions can be waived if you get
 *       permission from the copyright holder.
 *     * Public Domain —Where the work or any of its elements is in the
 *       public domain under applicable law, that status is in no way
 *       affected by the license.
 *     * Other Rights — In no way are any of the following rights affected
 *       by the license:
 *           o Your fair dealing or fair use rights, or other applicable
 *             copyright exceptions and limitations;
 *           o The author's moral rights;
 *           o Rights other persons may have either in the work itself or
 *             in how the work is used, such as publicity or privacy rights.
 *     * Notice — For any reuse or distribution, you must make clear to
 *       others the license terms of this work. The best way to do this is
 *       with a link to the web page given below.
 *
 * Licence (short): http://creativecommons.org/licenses/by-nc-sa/3.0/
 * License (long): http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode
*/


// Requires the entry template class
require("bib2tpl-entry.php");

// Some stupid functions
require('helper.inc.php');



/**
 * This class provides a method that parses bibtex files to
 * other text formats based on a template language. See
 *   http://lmazy.verrech.net/bib2tpl/
 * for documentation.
 *
 * @author Raphael Reitzig
 * @author Benjamin Piwowarski
 * @version 2.0
 */
class BibtexConverter
{
  /**
   * BibTex parser
   *
   * @access private
   * @var PaperciteStructures_BibTex
   */
    var $_parser;

  /**
   * Options array. May contain the following pairs:
   *   only  => array(['author' => regexp],['type' => regexp])
   *   group => (none|year|firstauthor|entrytype)
   *   order => (asc|desc)
   * @access private
   * @var array
   */
    var $_options;

  /**
   * Helper object with support functions.
   * @access private
   * @var Helper
   */
    var $_helper;


  /**
   * Global variables that can be accessed in the template
   */
    var $_globals;

  /**
   * Constructor.
   *
   * @access public
   * @param array options Options array. May contain the following pairs:
   *   only  => array(['author' => 'regexp'],['entrytype' => 'regexp'])
   *   group => (none|year|firstauthor|entrytype)
   *   group-order => (asc|desc|none)
   *   sort => (none|year|firstauthor|entrytype)
   *   order => (asc|desc|none)
   *   key_format => (numeric|cite)
   *   lang  => any string $s as long as proper lang/$s.php exists
   * @return void
   */
    function __construct($options = array(), &$template, &$entry_template)
    {
        $this->_template = &$template;
        $this->_entry_template = &$entry_template;

      //  $this->_parser = new PaperciteStructures_BibTex(array('removeCurlyBraces' => true));

      // Default options
        $this->_options = array(
        'only'  => array(),

        'anonymous-whole' => false,

        'group' => 'year',
        'group_order' => 'desc',

        'sort' => 'none',
        'order' => 'none',

        'lang' => 'en',

        'key_format' => 'numeric',
      
        'limit' => 0,
      
        'highlight' => ''
        );

      // Overwrite specified options
        foreach ($this->_options as $key => $value) {
            if (array_key_exists($key, $options)) {
                $this->_options[$key] = $options[$key];
            }
        }

      /* Load translations.
       * We assume that the english language file is always there.
       */
        if (is_readable(dirname(__FILE__).'/lang/'.$this->_options['lang'].'.php')) {
            require('lang/'.$this->_options['lang'].'.php');
        } else {
            require('lang/en.php');
        }
        $this->_options['lang'] = $translations;
        $this->_helper = new Bib2TplHelper($this->_options);
    }


  /**
   * Set a global variable
   */
    function setGlobal($name, $value)
    {
        $this->_globals[$name] = $value;
    }


  /**
   * Converts the given string in bibtex format to a string whose format
   * is defined by the passed template string.
   *
   * @access public
   * @param string bibtex Bibtex code
   * @param string template template code
   * @return mixed Result string or PEAR_Error on failure
   */
    function convert($bibtex)
    {
      // TODO Eliminate LaTeX syntax

        $this->_parser->loadString($bibtex);
        $stat = $this->_parser->parse();

        if (!$stat) {
            return $stat;
        }

        return $this->display($this->_parser->data);
    }

  /**
   *
   * Display a pre-selected set of entries (group, sort, and
   * translate)
   *
   * @access public
   * @param string bibtex Bibtex code
   * @param string template template code
   * @return mixed Result string or PEAR_Error on failure
   */
    function display(&$data)
    {
        $this->_pre_process($data);
        $data = $this->_group($data);
        $data = $this->_sort($data);
        $this->_post_process($data);

        $this->count = 0;
        $text = $this->_translate($data);
        return array("text" => &$text, "data" => &$data);
    }

  /**
   * This function filters data from the specified array that should
   * not be shown. Filter criteria are specified at object creation.
   *
   * This function also adds values that are assumed to be existent
   * later if they do not exist, namely <code>entryid</code>,
   * <code>firstauthor = author[0]</code>, <code>year</code> and
   * <code>month</code>. Furthermore, entries whose entrytype is not
   * translated in the specified language file are put into a distinct
   * group.
   *
   * @access private
   * @param array data Unfiltered data, that is array of entries
   * @return array Filtered data as array of entries
   */
    function _filter($data)
    {
        $result = array();

        $id = 0;
        foreach ($data as $entry) {
            if ((   empty($this->_options['only']['author'])
               || preg_match(
                   '/'.$this->_options['only']['author'].'/i',
                   $this->_entry_template->niceAuthors($entry['author'])
               ))
            && (   empty($this->_options['only']['entrytype'])
               || preg_match(
                   '/'.$this->_options['only']['entrytype'].'/i',
                   $entry['entrytype']
               )) ) {
                $entry['year'] = empty($entry['year']) ? '0000' : $entry['year'];
                if (empty($this->_options['lang']['entrytypes'][$entry['entrytype']])) {
                    $entry['entrytype'] = $this->_options['lang']['entrytypes']['unknown'];
                }
                $result[] = $entry;
            }
        }

        return $result;
    }

 /**
   * This function do some pre-processing on the entries
   */
    function _pre_process(&$data)
    {
        $id = 0;
        foreach ($data as &$entry) {
            $entry['firstauthor'] = isset($entry['author']->authors) ? $entry['author']->authors[0]["surname"] : "";
            $entry['entryid'] = $id++;
        }
    }



  /**
   * This function do some post-processing on the grouped & ordered list of publications.
   * In particular, it sets the key.
   */
    function _post_process(&$data)
    {
        $count = 0;
        foreach ($data as &$group) {
            foreach ($group as &$entry) {
                $count++;
      
                switch ($this->_options["key_format"]) {
                    case "numeric":
                        $entry["key"] = $count;
                        break;
                    case "cite":
                        $entry["key"] = $entry["cite"];
                        break;
                    default:
                        $entry["key"] = "?";
                }
            }
        }
    }

  /**
   * This function groups the passed entries according to the criteria
   * passed at object creation.
   *
   * @access private
   * @param array data An array of entries
   * @return array An array of arrays of entries
   */
    function _group($data)
    {
        $result = array();

        if ($this->_options['group'] !== 'none') {
            foreach ($data as $entry) {
                $target =  $this->_options['group'] === 'firstauthor'
                ? $this->_entry_template->niceAuthor($entry['firstauthor'])
                  : $entry[$this->_options['group']];

                if (empty($result[$target])) {
                    $result[$target] = array();
                }

                $result[$target][] = $entry;
            }
        } else {
            if ($this->_options["anonymous-whole"]) {
                $result[""] = $data;
            } else {
                $result[$this->_options['lang']['all']] = $data;
            }
        }

        return $result;
    }

  /**
   * This function sorts the passed group of entries and the individual
   * groups if there are any.
   *
   * @access private
   * @param array data An array of arrays of entries
   * @return array A sorted array of sorted arrays of entries
   */
    function _sort(&$data)
    {
      // Sort groups if there are any
        if ($this->_options['group_order'] !== 'none') {
            uksort($data, array($this->_helper, 'group_cmp'));
        }

      // Sort individual groups
        if ($this->_options["sort"] != "none") {
            foreach ($data as &$group) {
                uasort($group, array($this->_helper, 'entry_cmp'));
            }
        }

        return $data;
    }

  /**
   * This function inserts the specified data into the specified template.
   * For template syntax see class documentation or examples.
   *
   * @access private
   * @param array data An array of arrays of entries
   * @param string template The used template
   * @return string The data represented in terms of the template
   */
    function _translate($data)
    {
        $result = $this->_template;
        if (!$result) {
            throw new \Exception("Template is empty");
        }

      // Replace global values
        $result = preg_replace('/@globalcount@/', $this->_helper->lcount($data, 2), $result);
        $result = preg_replace('/@globalgroupcount@/', count($data), $result);
        $result = preg_replace('/[\n\r]+/', ' ', $result);
        $match = array();

      // Extract entry template
        $pattern = '/@\{entry@(.*?)@\}entry@/s';
        preg_match($pattern, $result, $match);
        $this->full_entry_tpl = $match[1];
        $result = preg_replace($pattern, "@#fullentry@", $result);

      // Extract group template
        $pattern = '/@\{group@(.*?)@\}group@/s';
        preg_match($pattern, $result, $match);
        $this->group_tpl = $match[1];
        $result = preg_replace($pattern, "@#group@", $result);

      // The data to be processed
        $this->_data = &$data;
    
      // The count
        $this->_globals["positionInList"] = 1;

      // "If-then-else" stack
        $this->_ifs = array(true);

      // Now, replace
        return preg_replace_callback(BibtexConverter::$mainPattern, array($this, "_callback"), $result);
    }

    static $mainPattern = "/@([^@]+)@([^@]*)/s";

  /**
   * Main callback function
   */
    function _callback($match)
    {

        $condition = $this->_ifs[sizeof($this->_ifs)-1];

      // --- [ENDIF]
        if ($match[1][0] == ';') {
          // Remove last IF expression value
            array_pop($this->_ifs);
            $condition = $this->_ifs[sizeof($this->_ifs)-1];
            if ($condition == 1) {
                return $match[2];
            }
            return "";
        }

      // --- [IF]
        if ($match[1][0] == '?') {
            if ($condition != 1) {
              // Don't evaluate if not needed
              // -1 implies to evaluate to false the alternative (ELSE)
                $this->_ifs[] = -1;
                return "";
            }
      
            $tests = preg_split("/\|\|/", substr($match[1], 1));
    
            $condition = true;
            foreach ($tests as $test) {
                $matches = array();
                preg_match("/^(#?[\w]+)(?:([~=><])([^@]+))?$/", $test, $matches);

                if (count($matches) == 0) {
                    $value = '';
                } else {
                    $value = $this->_get_value($matches[1]);
                }              //print "<div>Compares $value ($matches[1]) [$matches[2]] $matches[3]</div>";
                switch (sizeof($matches) > 2 ? $matches[2] : "") {
                    case "":
                        $condition = $value ? true : false;
                        break;
                    case "=":
                        $condition = $value == $matches[3];
                        break;
                    case "~":
                        $condition = preg_match("/$matches[3]/", $value);
                        break;
                    case ">":
                        $condition =  (float)$value > (float)$matches[3];
                        break;
                    case "<":
                        $condition =  (float)$value < (float)$matches[3];
                        break;
                    default:
                        $condition = false;
                }

              // And
                if ($condition) {
                    break;
                }
            }

            $this->_ifs[] = $condition ? 1 : 0;
            if ($condition) {
                return $match[2];
            }
            return "";
        }

      // --- [ELSE]
        if ($match[1][0] == ':') {
          // Invert the expression (if within an evaluated condition)
            $condition = $condition < 0 ? -1 : 1 - $condition;
            $this->_ifs[sizeof($this->_ifs)-1] = $condition;
            if ($condition == 1) {
                return $match[2];
            }
            return "";
        }

      // Get the current condition status
        if ($condition != 1) {
            return "";
        }

      // --- Group loop
        if ($match[1] == "#group") {
            $groups = "";
            foreach ($this->_data as $groupkey => &$group) {
                if (is_array($groupkey)) {
                  // authors
                    $groupkey = $this->_helper->niceAuthor($key);
                } elseif ($this->_options['group'] === 'entrytype') {
                    $groupkey = $this->_options['lang']['entrytypes'][$groupkey];
                }
          
                // Set the different global variables and parse
                $this->_globals["groupkey"] = $groupkey;
                $this->_globals["groupid"] = md5($groupkey);
                $this->_globals["groupcount"] = count($group);
                $this->_group = &$group;
                $groups .= preg_replace_callback(BibtexConverter::$mainPattern, array($this, "_callback"), $this->group_tpl);
            }

            $this->_globals["groupkey"] = null;
            $this->_group = null;

            return $groups . $match[2];
        }

      // --- Full entry loop
        if ($match[1] == "#fullentry") {
            $entries = "";
            $limit = $this->_options["limit"];
            $groupPosition = 0;
            foreach ($this->_group as &$entry) {
                if ($limit > 0 && $limit <= $this->count) {
                  // Stop if we reached the limit
                    break;
                }
                $this->count += 1;
                $groupPosition++;

                $this->_globals["positionInGroup"] = $groupPosition;
                $this->_globals["positionInList"] = $this->count;

                $this->_entry = $entry;
                $entries .= preg_replace_callback(BibtexConverter::$mainPattern, array($this, "_callback"), $this->full_entry_tpl);
                $this->_globals["positionInList"]++;
            }
            unset($this->_entry);
            return $entries . $match[2];
        }

      // --- Entry
        if ($match[1] == "#entry") {
          // Formats one bibtex
            if ($this->_entry["entrytype"]) {
                $type = $this->_entry["entrytype"];
                $entryTpl = $this->_entry_template->get($type);
              //print "<div><b>$type</b>: ". htmlentities($entryTpl). "</div>";
                $this->_globals["positionInGroup"] = $this->count;
                $this->_globals["positionInList"] = $this->count;
                $t=  preg_replace_callback(BibtexConverter::$mainPattern, array($this, "_callback"), $entryTpl) . $match[2];
            } else {
                if (isset($this->_entry["cite"])) {
                    $t = "<span style='color:red'>Unknown bibtex entry with key [".$this->_entry["cite"] ."]</span>" . $match[2];
                } else {
                    $t = "<span style='color:red'>Unknown bibtex entry with key [?]</span>" . $match[2];
                }
            }
            return $t;
        }

      // --- Normal processing
        return $this->_get_value($match[1]).$match[2];
    }

    function _get_value($name)
    {
      // --- Get the options
        $v = null;
        $count = false;
        $modifier = "";

        if ($name[0] == "#") {
            $name = substr($name, 1);
            $count = true;
        }

        $pos = strpos($name, ":");
        if ($pos > 0) {
            $modifier = substr($name, $pos+1);
            $name = substr($name, 0, $pos);
        }

      // --- If we have an entry
        if (isset($this->_entry) && array_key_exists($name, $this->_entry)) {
            $v = $this->_entry[$name];
        } // Global variable
        elseif (array_key_exists($name, $this->_globals)) {
            $v = $this->_globals[$name];
        }

      // --- post processing

        if ($count) {
            return $this->_entry_template->count($v);
        }

        $str = $this->_entry_template->format($v);
        if ($name != 'bibtex') {
          // replace newlines with spaces, to avoid PHP converting them to <br/>
            $str = preg_replace("/[\r\n]+/", " ", $str);
        }

        switch ($modifier) {
            case "sanitize":
                $str = sanitize_title($str);
                break;
            case "strip":
                $str = wp_strip_all_tags($str);
            case "protect":
                $str = htmlentities($str);
            case "html":
                break;

            default:
              // TODO: should report an error here?
                break;
        }
      
      // highlight authors
        if ($name == 'author' || $name == 'editor') {
            if (!empty($this->_options['highlight'])) {
                $str = preg_replace('~\\b('.$this->_options['highlight'].')\\b~', '<span class="papercite_highlight">$0</span>', $str);
            }
        }
      
        return $str;
    }
}
