<?php
/*
 * By Raphael Reitzig, 2010
 * code@verrech.net
 * http://lmazy.verrech.net
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
 *     * Public Domain — Where the work or any of its elements is in the
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
?>
<?php

// Use the slightly modified BibTex parser from PEAR.
require('lib/BibTex.php');

// Some stupid functions
require('helper.inc.php');

/**
 * This class provides a method that parses bibtex files to
 * other text formats based on a template language. See
 *   http://lmazy.verrech.net/bib2tpl/
 * for documentation.
 *
 * @author Raphael Reitzig
 * @version 1.0
 */
class BibtexConverter
{
  /**
   * BibTex parser
   *
   * @access private
   * @var Structures_BibTex
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
   *   lang  => any string $s as long as proper lang/$s.php exists
   * @return void
   */
  function BibtexConverter($options=array())
  {
    $this->_parser = new Structures_BibTex(array('removeCurlyBraces' => true));

    // Default options
    $this->_options = array(
      'only'  => array(),

      'anonymous-whole' => false,

      'group' => 'year',
      'group-order' => 'desc',

      'sort' => 'none',
      'order' => 'none',

      'lang' => 'en'
    );

    // Overwrite specified options
    foreach ( $this->_options as $key => $value )
    {
      $this->_options[$key] = $options[$key];
    }

    /* Load translations.
     * We assume that the english language file is always there.
     */
    if ( is_readable(dirname(__FILE__).'/lang/'.$this->_options['lang'].'.php') )
    {
      require('lang/'.$this->_options['lang'].'.php');
    }
    else
    {
      require('lang/en.php');
    }
    $this->_options['lang'] = $translations;

    $this->_helper = new Helper($this->_options);
  }


  /**
   * Set a global variable
   */
  function setGlobal($name, $value) {
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
  function convert($bibtex, $template)
  {
    // TODO Eliminate LaTeX syntax

    $this->_parser->loadString($bibtex);
    $stat = $this->_parser->parse();

    if ( !$stat ) {
      return $stat;
    }

    $data = $this->_parser->data;
    $data = $this->_filter($data);
    $data = $this->_group($data);
    $data = $this->_sort($data);

    return $this->_translate($data, $template);
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
  function display(&$data, $template)
  {
    $data = $this->_group($data);
    $data = $this->_sort($data);

    $text = $this->_translate($data, $template);
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
    foreach ( $data as $entry ) {
      if (    (   empty($this->_options['only']['author'])
               || preg_match('/'.$this->_options['only']['author'].'/i',
                             $this->_helper->niceAuthors($entry['author'])))
           && (   empty($this->_options['only']['entrytype'])
               || preg_match('/'.$this->_options['only']['entrytype'].'/i',
                             $entry['entrytype'])) )
      {
        $entry['firstauthor'] = $entry['author'][0];
        $entry['entryid'] = $id++;
        $entry['year'] = empty($entry['year']) ? '0000' : $entry['year'];
        if ( empty($this->_options['lang']['entrytypes'][$entry['entrytype']]) )
        {
          $entry['entrytype'] = $this->_options['lang']['entrytypes']['unknown'];
        }
        $result[] = $entry;
      }
    }

    return $result;
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

    if ( $this->_options['group'] !== 'none' )
    {
      foreach ( $data as $entry )
      {
        $target =   $this->_options['group'] === 'firstauthor'
	  ? $this->_helper->niceAuthor($entry['firstauthor'])
                  : $entry[$this->_options['group']];

        if ( empty($result[$target]) )
        {
          $result[$target] = array();
        }

        $result[$target][] = $entry;
      }
    }
    else
    {
      if ($this->_options["anonymous-whole"]) 
	$result[""] = $data;
      else
	$result[$this->_options['lang']['all']] = $data;
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
    if ( $this->_options['group-order'] !== 'none' )
    {
      uksort($data, array($this->_helper, 'group_cmp'));
    }

    // Sort individual groups
    if ( $this->_options["sort"] != "none" ) 
      foreach ( $data as &$group )
	{
	  uasort($group, array($this->_helper, 'entry_cmp'));
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
  function _translate($data, $template)
  {
    $result = $template;

    // Replace global values
    $result = preg_replace('/@globalcount@/', $this->_helper->lcount($data, 2), $result);
    $result = preg_replace('/@globalgroupcount@/', count($data), $result);

    $pattern = '/@\{group@(.*?)@\}group@/s';

    // Extract group template
    $group_tpl = array();
    preg_match($pattern, $template, $group_tpl);
    $group_tpl = $group_tpl[1];

    // Translate all groups
    $groups = '';
    foreach ( $data as $groupkey => $group )
    {
      $groups .= $this->_translate_group($groupkey, $group, $group_tpl);
    }

    return preg_replace($pattern, $groups, $result);
  }

  /**
   * This function translates one entry group
   *
   * @access private
   * @param string key The rendered group's key
   * @param array data Array of entries in this group
   * @param string template The group part of the template
   * @return string String representing the passed group wrt template
   */
  function _translate_group($key, $data, $template)
  {
    $result = $template;

    // Replace group values
    if ( is_array($key) )
    {
      $key = $this->_helper->niceAuthor($key);
    }
    elseif ( $this->_options['group'] === 'entrytype' )
    {
      $key = $this->_options['lang']['entrytypes'][$key];
    }
    $result = preg_replace('/@groupkey@/', $key, $result);
    $result = preg_replace('/@groupid@/', md5($key), $result);
    $result = preg_replace('/@groupcount@/', count($data), $result);

    $pattern = '/@\{entry@(.*?)@\}entry@/s';

    // Extract entry template
    $entry_tpl = array();
    preg_match($pattern, $template, $entry_tpl);
    $entry_tpl = $entry_tpl[1];

    // Translate all entries
    $entries = '';
    foreach ( $data as $entry )
    {
      $entries .= $this->_translate_entry($entry, $entry_tpl);
    }

    return preg_replace($pattern, $entries, $result);
  }

  /**
   * This function translates one entry
   *
   * @access private
   * @param array entry Array of fields
   * @param string template The entry part of the template
   * @return string String representing the passed entry wrt template
   */
  function _translate_entry($entry, $template)
  {
    $result = $template;

    // Resolve all conditions
    $result = $this->_resolve_conditions($entry, $result);

    // Global variables
    $this->currentEntry = &$entry;
    return preg_replace_callback('/@([^:@]+)(?::([^@]+))?@/', array($this, "_translate_variables"), $result);
  }

  function _translate_variables($input) {
    // Special case: author
    if ($input[1] == "author") {
      return $this->_helper->niceAuthors($this->currentEntry["author"], $input[2]);
    }

    // Entry variable
    if (array_key_exists($input[1], $this->currentEntry)) 
      return $this->currentEntry[$input[1]];

    // Global variable
    if (array_key_exists($input[1], $this->_globals)) {
      return $this->_globals[$input[1]];
    }

    return $input[0];
  }

  /**
   * This function eliminates conditions in template parts.
   *
   * @access private
   * @param array entry Entry with respect to which conditions are to be
   *                    solved.
   * @param string template The entry part of the template.
   * @return string Template string without conditions.
   */
  function _resolve_conditions($entry, $string) {
    $pattern = '/@\?(\w+?)@(.*?)(@:\1@(.*?)){0,1}@;\1@/s';
    /* Group 1: field
     * Group 2: then
     *[Group 4: else]
     */

    $match = array();

    /* Would like to do
     *    preg_match_all($pattern, $string, $matches);
     * to get all matches at once but that results in Segmentation
     * fault. Therefore iteratively:
     */
    while ( preg_match($pattern, $string, $match) )
    {
      $resolved = '';
      if ( !empty($entry[$match[1]]) )
      {
        $resolved = $match[2];
      }
      elseif ( !empty($match[4]) )
      {
        $resolved = $match[4];
      }

      // Recurse to cope with nested conditions
      $resolved = $this->_resolve_conditions($entry, $resolved);

      $string = str_replace($match[0], $resolved, $string);
    }

    return $string;
  }
}

?>
