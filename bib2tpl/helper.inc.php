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

/**
 * Provides helping functions in order to keep clutter from the main file.
 *
 * @author Raphael Reitzig
 * @version 1.0
 */
class Helper
{

  /**
   * Copy of main class's options
   */
  var $_options;

  /**
   * Constructor.
   *
   * @access public
   * @param array options Options array with same semantics as main class.
   */
  function Helper($options=array())
  {
    $this->_options = $options;
  }
  
  /**
   * Obtains a month number from the passed entry.
   *
   * @access private
   * @param array entry An entry
   * @return string The passed entry's month number. <code>00</code> if
   *                the month could not be recognized.
   */
  function _e2mn($entry) {
    $month = empty($entry['month']) ? '' : $entry['month'];
    
    $result = '00';
    $month = strtolower($month);

    // This is gonna get ugly; other solutions?
    $pattern = '/^'.$month.'/';
    if ( preg_match('/^\d[\d]$/', $month) )
    {
      return strlen($month) == 1 ? '0'.$month : $month;
    }
    else
    {
      foreach ( $this->_options['lang']['months'] as $number => $name )
      {
        if ( preg_match($pattern , $name) )
        {
          $result = $number;
          break;
        }
      }
    }

    return result;
  }

  /**
   * Compares two group keys for the purpose of sorting.
   *
   * @access public
   * @param string k1 group key one
   * @param string k2 group key two
   * @return int integer (<,=,>) zero if k1 is (less than,equal,larger than) k2
   */
  function group_cmp($k1, $k2)
  {
    return  $this->_options['group_order'] !== 'desc'
          ? strcmp($k1, $k2)
          : -strcmp($k1, $k2);
  }

  /**
   * Compares two entries for the purpose of sorting.
   *
   * @access public
   * @param string k1 entry key one
   * @param string k2 entry key two
   * @return int integer (<,=,>) zero if entry[$k1] is
   *                     (less than,equal,larger than) entry[k2]
   */
  function entry_cmp($e1, $e2)
  {
    // Get the values
    $name = $this->_options['sort'];

    // Sort always descending by date inside the group
    if ($name == "year") {
      $order = -strcmp($e1['year'].$this->_e2mn($e1),
		       $e2['year'].$this->_e2mn($e2));
    } else if ($name == "firstauthor") {
      $order = -strcmp($e1["author"][0]["last"], $e2["author"][0]["last"]);
    } else if ($name == "author") {
      $n = min(sizeof($e1["author"]), sizeof($e2["author"]));
      for($i = 0; $i < $n; $i++) {
	$order = -strcmp($e1["author"][$i]["last"], $e2["author"][$i]["last"]);
	if ($order != 0) 
	  break;
      }
    } else 
      $order = -strcmp($e1[$name], $e2[$name]);


    return $this->_options['order'] === 'desc' 
      ? $order
      : -$order;
  }

  /**
   * Counts elements in the specified array at the specified level.
   * For depth<=1, lcount equals count.
   *
   * @access public
   * @param array array Array to count
   * @param int depth Counting depth
   */
  function lcount($array, $depth=1)
  {
    $sum = 0;
    $depth--;

    if ( $depth > 0 )
    {
      foreach ( $array as $elem )
      {
        $sum += is_array($elem) ? $this->lcount($elem, $depth) : 0;
      }
    }
    else
    {
      foreach ( $array as $elem )
      {
        $sum += is_array($elem) ? 1 : 0;
      }
    }

    return $sum;
  }

  /**
   * This function takes an array of authors and renders is into a comma
   * separated list of authors. If no array is passed the value is returned
   * without change.
   *
   * @access public
   * @param array authors Value representing authors
   * @return string Either a string if an array was passed or input value
   *                otherwise.
   */
  function niceAuthors($authors, $options = array())
  {
    if ( is_array($authors) )
    {
      foreach ( $authors as $key => $author )
      {
        $authors[$key] = $this->niceAuthor($author, $options);
      }

      $authors = join(', ', $authors);
    }

    return $authors;
  }

  /**
   * This function takes an author and renders is into a string.
   * If no array is passed the value is returned without change.
   *
   * @access public
   * @param array authors Value representing authors
   * @return string Either a string if an array was passed or input value
   *               otherwise.
   */
  function niceAuthor($author, $options = array())
  {
    if ($options == "initials") {
      $firsts = preg_split("#[- ]#",$author["first"]);
      foreach($firsts as $first) 
	$initials .= "$first[0].";
      $author["first"] = $initials;
    }

    if ( is_array($author) )
    {
      // Remove empty name parts
      $author = array_filter($author);
      $author = join(' ', $author);
    }
    
    return $author;
  }
}
?>
