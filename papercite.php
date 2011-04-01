<?php

/*
  Plugin Name: papercite
  Plugin URI: http://www.bpiwowar.net/papercite
  Description: papercite enables to add bibtex entries formatted as HTML in wordpress pages and posts. The input data is the bibtex text file and the output is HTML. 
  Version: 0.3.0
  Author: Benjamin Piwowarski
  Author URI: http://www.bpiwowar.net
*/


/*  Copyright 2010 Benjamin Piwowarski (email: benjamin in the domain bpiwowar <DOT> net)

    Contributors:
    - Stefan Aiche: group by year option


    Sergio Andreozzi has written bib2html on which papercite is based
    Contributors (bib2html):
    - Cristiana Bolchini: cleaner bibtex presentation
    - Patrick Maué: remote bibliographies managed by citeulike.org or bibsonomy.org
    - Nemo: more characters on key
    - Marco Loregian: inverting bibtex and html
    - Łukasz Radliński: bug fixes & handling polish characters


    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Options

include("papercite_options.php");

class Papercite {

  static $option_names = array("format", "timeout", "file");

  var $parse = false;


  // List of publications for those citations
  var $bibshows = array();

  // Our caches (bibtex files and formats)
  var $cache = array();

  // Array of arrays of current citations
  var $cites = array();

  // Global replacements for cited keys
  var $keys = array();
  var $keyValues = array();

  // Global counter for unique references of each
  // displayed citation (used by bibshow)
  var $citesCounter = 0;
    
  // Global counter for unique reference of each
  // displayed citation
  var $counter = 0;

  /** Returns filename of cached version of given url  
   * @param url The URL
   * @param timeout The timeout of the cache
   */
  function getCached($url, $timeout = 3600) {
    // check if cached file exists
    $name = strtolower(preg_replace("@[/:]@","_",$url));
    $dir = dirname(__FILE__) . "/cache";
    $file = "$dir/$name.bib";


    // check if file date exceeds 60 minutes   
    if (! (file_exists($file) && (filemtime($file) + $timeout > time())))  {
      // not returned yet, grab new version
      // since wordpress 2.7, we can use the wp_remote_get function
      if (function_exists("wp_remote_get")) {
	$body = wp_remote_retrieve_body(wp_remote_get($url));
	if ($body) {
	  $f=fopen($file,"wb");
	  fwrite($f,$body);
	  fclose($f);
	} else return NULL;
      }
      else {
	$f=fopen($file,"wb");
	fwrite($f,file_get_contents($url));
	fclose($f);
      }
	
	
      if (!$f) echo "Failed to write file " . $file . " - check directory permission according to your Web server privileges.";
    }
	
    return $file;
  }

  /**
   * Init is called before the first callback
   */
  function init() {
  }

  /**
   * Check the different paths where papercite data can be stored
   * and return the first match, starting by the preferred ones
   */
  function getDataFile($uri) {
    foreach(array("../../papercite-data","../papercite-data", "data") as $path) {
      $path = dirname(__FILE__) . "/$path/$uri";
      if (file_exists($path)) return $path;
    }
    
  }

  /**
   * Get the bibtex data from an URI
   */
  function getData($biburi) {
    // (1) get the context
    if (!$this->cache[$biburi]) {
      if (strpos($biburi, "http://") === 0) 
	$bibFile = $this->getCached($biburi);
      else {
	$bibFile = $this->getDataFile("bib/$biburi");
      }
      
      if (!$bibFile || !file_exists($bibFile)) {
	return NULL;
      }

      // (2) Parse the BibTeX
      if (file_exists($bibFile)) {
	$data = file_get_contents($bibFile);
	if (!empty($data)) {
 
	  $this->_parser = new Structures_BibTex(array('removeCurlyBraces' => true));
	  $this->_parser->loadString($data);
	  $stat = $this->_parser->parse();
	  if ( !$stat ) {
	    return $stat;
	  }
	
	  $this->cache[$biburi] = $this->_parser->data;
	}
      }

    }


    return $this->cache[$biburi];
  }
    
  /** 
   * Returns the path to the pdf given a bibtex key
   */
  function pdf($entry) {
    $id = strtolower(preg_replace("@[/:]@", "-", $entry["bibtexCitation"]));

    foreach(array("../../papercite-data/pdf","../papercite-data/pdf", "data") as $subfolder) {
      if (file_exists(dirname(__FILE__) . "/$subfolder/" . $id . ".pdf")) {
	return " <a href='" .  get_bloginfo('wpurl') . "/wp-content/plugins/papercite/$subfolder/$id.pdf' title='Go to document'><img src='" . get_bloginfo('wpurl') . "/wp-content/plugins/papercite/pdf.png' width='10' height='10' alt='PDF' /></a>";
      }
    }

    return '';
  }
  
  // this function formats a bibtex code in order to be readable
  // when appearing in the modal window
  function formatBibtex($entry){
    $order = array("},");
    $replace = "}, <br />\n &nbsp;";
    
    $entry = preg_replace('/\s\s+/', ' ', trim($entry));
    $new_entry = str_replace($order, $replace, $entry);
    $new_entry = str_replace(", author", ", <br />\n &nbsp;&nbsp;author", $new_entry);
    $new_entry = str_replace(", Author", ", <br />\n &nbsp;&nbsp;author", $new_entry);
    $new_entry = str_replace(", AUTHOR", ", <br />\n &nbsp;&nbsp;author", $new_entry);
    $new_entry = preg_replace('/\},?\s*\}$/', "}\n}", $new_entry); 
    return $new_entry;
  }

 

  /**
   * Main entry point: Handles a match in the post
   */
  function process(&$matches) {
    $debug = false;

    // --- Initialisation ---
    
    // Includes once
    include_once("bib2tpl/bibtex_converter.php");


    // Get the options   
    $command = $matches[1];

    // Get all the options pairs and store them
    // in the $options array
    $options_pairs = array();
    preg_match_all("/\s*(?:(\w+)=(\S+))(\s+|$)/", $matches[2], $options_pairs, PREG_SET_ORDER);

    // Set preferences, by order of increasing priority
    // (0) Set in
    // (1) From the preferences
    // (2) From the custom fields
    // (3) From the general options
    $options = array("format" => "IEEE", "group" => "none");

    // Get general preferences
    if (!$this->pOptions)
      $this->pOptions = &get_option('papercite_options');

    foreach(self::$option_names as &$name) {
      if (array_key_exists($name, $this->pOptions))
	$options[$name] = $this->pOptions[$name];
      $custom_field = get_post_custom_values("papercite_$name");
      if (sizeof($custom_field) > 0)
	$options[$name] = $custom_field[0];
    }

    foreach($options_pairs as $x) {
      $options[$x[1]] = $x[2];
    }

    // --- Compatibility issues
    if (array_key_exists("groupByYear", $options) && (strtoupper($options["groupByYear"]) == "TRUE"))
	$options["group"] = "year";

    $tplOptions = array("group" => $options["group"], "order" => $options["order"]);
    $data = null;

    
    // --- Process the commands ---
    switch($command) {

      /*
	"bibtext" command
       */
    case "bibtex":
      // --- Filter the data
      $entries = $this->getData($options["file"]);
      if (!$entries) return "<span style='color: red'>[Could not find the bibliography file(s)]</span>";
 
      if (array_key_exists('key', $options)) {
	// Select only specified entries
	$keys = split(",", $options["key"]);
	$a = array();
	$n = 0;
	foreach($entries as $entry) {
	  if (in_array($entry["cite"], $keys)) {
	    $a[] = $entry;
	    $n = $n + 1;

	    // We found everything, early break
	    if ($n == sizeof($keys)) break;
	  }
	}
	$entries = $a;
      } else {
	// Based on the entry types
	$allow = $options["allow"];
	$deny = $options["deny"];
	if ($allow || $deny) {
	  $allow = $allow ? split(",",$allow) : false;
	  $deny =  $deny ? split(",", $deny) : false;

	  $entries2 = $entries;
	  $entries = array();
	  foreach($entries2 as &$entry) {
	    $t = $entry["entrytype"];
	    if ((!$allow || in_array($t, $allow)) && (!$deny || !in_array($t, $deny))) {
	      $entries[] = $entry;
	    }
	  }
	}
	    
      } 
      
      foreach($entries as &$entry)
	$entry["key"] = $entry["cite"];
      return  $this->showEntries($entries, $tplOptions, false);

      /*
	bibshow / bibcite commands
       */
    case "bibshow":
      $data = $this->getData($options["file"]);
      if (!$data) return "<span style='color: red'>[Could not find the bibliography file(s)]</span>";

      $refs = array();
      foreach($data as &$entry) {
	$key = $entry["cite"];
	$refs[$key] = &$entry;
      }

      array_push($this->bibshows, &$refs);
      $this->cites[] = array();
      break;

      // Just cite
    case "bibcite":
      if (sizeof($this->bibshows) == 0)  
	return "[<span title=\"Unkown reference: $key\">?</span>]";

      $key = $options["key"];
      $refs = &$this->bibshows[sizeof($this->bibshows)-1];
      $cites = &$this->cites[sizeof($this->cites)-1];

      // First, get the corresponding entry
      if (array_key_exists($key, $refs)) {
	$num = $cites[$key];

	// Did we already cite this?
	if (!$num) {
	  // no, register this
	  $id = "BIBCITE%%%" . $this->citesCounter;
	  $this->citesCounter++;
	  $num = sizeof($cites);
	  $cites[$key] = array($num, $id);
	}
	return "[$id]";
      }

      return "[<span title=\"Unkown reference: $key\">?</span>]";

    case "/bibshow":
      // select from cites
      if (sizeof($this->bibshows) == 0) return "";
      // Remove the array from the stack
      $data = &array_pop($this->bibshows);
      $cites = &array_pop($this->cites);
      $refs = array();

      // Order the citations according to citation order
      // (might be re-ordered latter)
      foreach($data as $key => &$entry) {
	$num = $cites[$key];
	if ($num) {
	  $refs[$num[0]] = $entry;
	  // Set the numeric key (might be changed)
	  $refs[$num[0]]["key"] = $num[0] + 1;
	  $refs[$num[0]]["pKey"] = $num[1];
	}
      }
      // grouping of bibentries by year is switched of by default
      // for this case, to allow the entries to show up in the 
      // order of usage
      return $this->showEntries($refs, $tplOptions, true);
      
    default:
      return "[error in papercite: unhandled]";
    }
  }


  function toDownload($entry) {
    if (array_key_exists('url',$entry)){
      $string = " <a href='" . $entry['url'] . "' title='Go to document'><img src='" . get_bloginfo('wpurl') . "/wp-content/plugins/papercite/external.png' width='10' height='10' alt='Go to document' /></a>";
      return $string;
    } else if (array_key_exists('doi', $entry)) {
      $string = " <a href='http://dx.doi.org/" . $entry['doi'] . "' title='Go to document'><img src='" . get_bloginfo('wpurl') . "/wp-content/plugins/papercite/external.png' width='10' height='10' alt='Go to document' /></a>";
      return $string;
    }
    return '';
  } 

  /**
   * Show a set of entries
   * @param refs An array of references
   * @param options The options to pass to bib2tpl
   * @param getKeys Keep track of the keys for a final substitution
   */
  function showEntries(&$refs, &$options, $getKeys) {
    $bib2tpl = new BibtexConverter($options);

    foreach($refs as &$ref)
      $ref["entryid"] = $this->counter++;

    $r =  $bib2tpl->display($refs, file_get_contents(dirname(__FILE__) . "/tpl/default.tpl"));
    if ($getKeys) {
      foreach($refs as &$group)
	foreach($group as &$ref) {
	  $this->keys[] = $ref["pKey"];
	  $this->keyValues[] = $ref["key"];
	}
    }
    return $r["text"];
  }
}


// -------------------- Interface with WordPress


// --- Head of the HTML ----
function papercite_head() {
  if (!function_exists('wp_enqueue_script')) {
    // In case there is no wp_enqueue_script function (WP < 2.6), we load the javascript ourselves
    echo "\n" . '<script src="'.  get_bloginfo('wpurl') . '/wp-content/plugins/papercite/js/jquery.js"  type="text/javascript"></script>' . "\n";
    echo '<script src="'.  get_bloginfo('wpurl') . '/wp-content/plugins/papercite/js/papercite.js"  type="text/javascript"></script>' . "\n";
  }
}

// --- Initialise papercite ---
function papercite_init() {
  global $papercite;

  if (function_exists('wp_enqueue_script')) {
    wp_register_script('papercite', get_bloginfo('wpurl') . '/wp-content/plugins/papercite/js/papercite.js', array('jquery'), '0.7');
    wp_enqueue_script('papercite');
  } 

  // Register and enqueue the stylesheet
  wp_register_style('papercite_css', WP_PLUGIN_URL . '/papercite/papercite.css' );
  wp_enqueue_style('papercite_css');

  $papercite = new Papercite();
}

// --- Callback function ----
function papercite_cb($myContent) {
  // Init
  $papercite = &$GLOBALS["papercite"];
  $papercite->init();
  
  // Process all at once
  $text = preg_replace_callback("/\[\s*((?:\/)bibshow|bibshow|bibcite|bibtex)(?:\s+([^[]+))?]/",
				array($papercite, "process"), $myContent);
  
  // Handles custom keys in bibshow
  return str_replace($papercite->keys, $papercite->keyValues, $text);
}

// --- Add the different handlers to WordPress ---
add_action('init', 'papercite_init');	
add_action('wp_head', 'papercite_head');
add_filter('the_content', 'papercite_cb',1);


?>
