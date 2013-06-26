<?php


/*
  Plugin Name: papercite
  Plugin URI: http://www.bpiwowar.net/papercite
  Description: papercite enables to add BibTeX entries formatted as HTML in wordpress pages and posts. The input data is the bibtex text file and the output is HTML. 
  Version: HEAD
  Author: Benjamin Piwowarski
  Author URI: http://www.bpiwowar.net
*/


/*  Copyright 2012  Benjamin Piwowarski  (email : benjamim@bpiwowar.net)

    Contributors:
    - Stefan Aiche: group by year option
    - Łukasz Radliński: bug fixes & handling polish characters
    - Some parts of the code come from bib2html (version 0.9.3) written by
    Sergio Andreozzi.

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

  // bibshow options stack
  var $bibshow_options = array();
  var $bibshow_tpl_options = array();

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
    $dir = WP_PLUGIN_DIR . "/papercite/cache";
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
	
	
      if (!$f) {
	echo "Failed to write file " . $file . " - check directory permission according to your Web server privileges.";
	return false;
      }
    }
	
    return array($file, WP_PLUGIN_URL."/papercite/cache/$name.bib");
  }

  static $bibtex_parsers = array("pear" => "Pear parser", "osbib" => "OSBiB parser");

  // Names of the options that can be set
  static $option_names = array("format", "timeout", "file", "bibshow_template", "bibtex_template", "bibtex_parser", "use_db", "auto_bibshow");

  static $default_options = 
	array("format" => "ieee", "group" => "none", "order" => "desc", "sort" => "none", "key_format" => "numeric",
	      "bibtex_template" => "default-bibtex", "bibshow_template" => "default-bibshow", "bibtex_parser" => "pear", "use_db" => false, "auto_bibshow" => false);
  /**
   * Init is called before the first callback
   */
  function init() {

    // Get general preferences & page wise preferences
    if (!$this->options) {
      $this->options =  papercite::$default_options;
      $pOptions = &get_option('papercite_options');

      // Use preferences if set to override default values
      if (is_array($pOptions)) {
	foreach(self::$option_names as &$name) {
	  if (array_key_exists($name, $pOptions) && sizeof($pOptions[$name]) > 0) {
	    $this->options[$name] = $pOptions[$name];
	  }
	}
      }

      // Use custom field values
      $custom_field = get_post_custom_values("papercite_$name");
      if (sizeof($custom_field) > 0)
	$this->options[$name] = $custom_field[0];

      // Upgrade if needed
      if ($this->options["bibtex_parser"] == "papercite") {
	$this->options["bibtex_parser"] = "osbib";
      }
      
    }
    
  }

  
  /**
   * Check the different paths where papercite data can be stored
   * and return the first match, starting by the preferred ones
   * @return either false (no match), or an array with the full
   * path and the URL
   */
  static function getDataFile($relfile) {
    global $wpdb; 

    // Multi-site case
    if (is_multisite()) {
      $subpath = '/blogs.dir/'. $wpdb->blogid . "/files/papercite-data/$relfile";
      $path = WP_CONTENT_DIR . $subpath;
      if (file_exists($path))
	      return array($path, WP_CONTENT_URL.$subpath);
    }

    if (file_exists(WP_CONTENT_DIR . "/papercite-data/$relfile"))
      return array(WP_CONTENT_DIR . "/papercite-data/$relfile", WP_CONTENT_URL . "/papercite-data/$relfile");

    if (file_exists(WP_PLUGIN_DIR . "/papercite/$relfile"))
      return array(WP_PLUGIN_DIR . "/papercite/$relfile", WP_PLUGIN_URL . "/papercite/$relfile");
	 
  }

  /** 
   * Check if a matching file exists, and add it to the bibtex if so
   * @param The key
   * @param 
   */
  function checkFiles(&$entry, $types) {
    $id = strtolower(preg_replace("@[/:]@", "-", $entry["cite"]));
    foreach($types as &$type) {
      $file = papercite::getDataFile("$type[0]/$id.$type[1]");
      if ($file) {
	      $entry[$type[0]] =  $file[1];
      }
    }
  }

  /**
   * Get the bibtex data from an URI
   */
  function &getData($biburis, $timeout = 3600) {
      global $wpdb, $papercite_table_name;

    
    // Loop over the different given URIs
    $array = explode(",", $biburis);
    $result = array();
    foreach($array as $biburi) {

      // (1) Get the context
      $data = FALSE;
      if (!$this->cache[$biburi]) {
      	if (strpos($biburi, "custom://") === 0) {
      	    $data = get_post_custom_values("papercite_" . substr($biburi, 9));
      	    if ($data) $data = $data[0];
      	}
    	else if (preg_match('#^(ftp|http)s?://#', $biburi) == 1) {
    	  $bibFile = $this->getCached($biburi, $timeout);
    	} else {
    	  $bibFile = $this->getDataFile("bib/$biburi");
    	}

	if ($data === FALSE && !($bibFile && file_exists($bibFile[0])))
	  continue;	


	// (2) Parse the BibTeX
	if ($data || file_exists($bibFile[0])) {
	    if (!$data) {
	        $fileTS = filemtime($bibFile[0]);
	    
        	// Check if we don't have the data in cache
            if ($this->useDb()) {
                // We use entrytype as a timestamp
                $dbTS = intval($wpdb->get_var($wpdb->prepare("SELECT entrytype FROM $papercite_table_name WHERE url=%s and bibtexid=''", "ts://" . $biburi)));
                if ($dbTS >= $fileTS) {
                	$result[$biburi] = $this->cache[$biburi] = "__DB__";
                	continue;
                } 
            }
        

    	    $data = file_get_contents($bibFile[0]);
        } 
    
	  if (!empty($data)) {
	    switch($this->options["bibtex_parser"]) {
	    case "pear": // Pear parser
	      $this->_parser = new Structures_BibTex(array('removeCurlyBraces' => true, 'extractAuthors' => true));
	      $this->_parser->loadString($data);
	      $stat = $this->_parser->parse();
	      
	      if ( !$stat )  return  $this->cache[$biburi] = false;
	      $this->cache[$biburi] = &$this->_parser->data;
	      break;

	    default: // OSBiB parser
	      $parser = new BibTexEntries();
	      if (!$parser->parse($data)) {
		$this->cache[$biburi] = false;
		continue;
	      } else {
		$this->cache[$biburi] = &$parser->data;
	      }
	      break;
	    }
	

	    // --- Add custom fields
	    foreach($this->cache[$biburi] as &$entry) {
	      $this->checkFiles($entry, array(array("pdf", "pdf")));
	    }
	    
	    // Save to DB
	    if (!$stringedFile && $this->useDb()) {
	        // First delete everything
	        $wpdb->query($wpdb->prepare("DELETE FROM $papercite_table_name WHERE url=%s", $biburi));
	        $code = true;
	        foreach($this->cache[$biburi] as &$value) {
	            $statement = $wpdb->prepare("INSERT INTO $papercite_table_name(url, bibtexid, entrytype, year, data) VALUES (%s,%s,%s,%s,%s)", 
	                            $biburi, $value["cite"], $value["entrytype"], $value["year"], maybe_serialize($value));
	            $code = $wpdb->query($statement);
	            if ($code === FALSE) {
	                break;
                }
	        }
	        if ($code !== FALSE) { 
	            $statement = $wpdb->prepare("INSERT INTO $papercite_table_name(url, bibtexid, entrytype) VALUES(%s,%s,%s)", "ts://".$biburi, "", $fileTS);
	            $code = $wpdb->query($statement);
	        } 
        }
	  }
	}
      }

      // Add to the list
      if ($this->cache[$biburi]) 
	      $result[$biburi] = $this->cache[$biburi];
    }
    
    if (sizeof($result) == 0) return false;
    return $result;
 
  }
    
    /** Returns true if papercite uses a database backend */
    function useDb() { return $this->options["use_db"]; }

  // Get the subset of keys present in the entries
  static function getEntriesByKey(&$entries, &$keys) {
      global $wpdb, $papercite_table_name;
        $a = array();
        $dbs = array();  
        $found = array();      
        foreach ($entries as $key => &$outer) {
          if ($outer == "__DB__") $dbs[] = $key;
          else foreach($outer as $entry) {
        	if (in_array($entry["cite"], $keys)) {
        	  $a[] = $entry;
        	  $found[] = $entry["cite"];
        	  $n = $n + 1;
        	  // We found everything, early break
        	  if ($n == sizeof($keys)) break;
        	}
          }
          if ($n == sizeof($keys)) break;
        }
        
        // Case where we have to check the db
        $unfound = array_diff($keys, $found);
        if ($dbs && sizeof($unfound) > 0) {
            $dbs = papercite::getDbCond($dbs);
            foreach($unfound as &$v) $v = '"' . $wpdb->escape($v) . '"';
            $keylist = implode(",", $unfound);
            $st = "SELECT data FROM $papercite_table_name WHERE $dbs and bibtexid in ($keylist)";
            $val = $wpdb->get_col($st);
            if ($val !== FALSE) { 
                foreach($val as &$data)              
                    $a[] = maybe_unserialize($data);
            }
        }
        
    return $a;
  } 

  /**
   * Main entry point: Handles a match in the post
   */
  function process(&$matches) {
      global $wpdb, $papercite_table_name;
    $debug = false;

    $post = null;
    //print(get_post($post)->post_modified_gmt);

    // --- Initialisation ---
    
    // Includes once the bibtex parser
    require_once(dirname(__FILE__) . "/lib/BibTex_" . $this->options["bibtex_parser"] . ".php");

    // Includes once the converter
    require_once("bib2tpl/bibtex_converter.php");

    // Get the options   
    $command = $matches[1];

    // Get all the options pairs and store them
    // in the $options array
    $options_pairs = array();
    preg_match_all("/\s*(?:([\w-_]+)=(\S+))(\s+|$)/", $matches[2], $options_pairs, PREG_SET_ORDER);

    // Set preferences, by order of increasing priority
    // (0) Set in
    // (1) From the preferences
    // (2) From the custom fields
    // (3) From the general options
    // $this->options has already processed the steps 0-2
    $options = $this->options;

    // Gets the options from the command
    foreach($options_pairs as $x) {
      if ($x[1] == "template") {
	// Special case of template: should overwrite the corresponding command template
	$options["${command}_$x[1]"] = $x[2];
      } else
	$options[$x[1]] = $x[2];
    }

    // --- Compatibility issues
    if (array_key_exists("groupByYear", $options) && (strtoupper($options["groupByYear"]) == "TRUE")) {
	$options["group"] = "year";
	$options["group_order"] = "desc";
    }

    $tplOptions = array(
			"anonymous-whole" => true, // for compatibility in the output
			"group" => $options["group"], "group_order" => $options["group_order"], 
			"sort" => $options["sort"], "order" => $options["order"],
			"key_format" => $options["key_format"]);
    $data = null;

    // --- Process the commands ---
    switch($command) {

       // bibtex command: 
    case "bibtex":
      // --- Filter the data
      $entries = $this->getData($options["file"], $options["timeout"]);
      if (!$entries) 
          return "<span style='color: red'>[Could not find the bibliography file(s)".
              (current_user_can("edit_post") ? " with name [".htmlspecialchars($options["file"])."]" : "") ."</span>";
 
      if (array_key_exists('key', $options)) {
    	// Select only specified entries
    	$keys = split(",", $options["key"]);
    	$a = array();
    	$n = 0;

    	$result = papercite::getEntriesByKey($entries, $keys);
      } else {
	// Based on the entry types
	$allow = $options["allow"];
	$deny = $options["deny"];
    $allow = $allow ? split(",",$allow) : Array();
    $deny =  $deny ? split(",", $deny) : Array();

	  $result = array();
	  $dbs = array();
	  foreach($entries as $key => &$outer) {
  	    if ($outer == "__DB__")
  	        $dbs[] = $key;
  	    else
    	    foreach($outer as &$entry) {
    	      $t = &$entry["entrytype"];
    	      if ((sizeof($allow)==0 || in_array($t, $allow)) && (sizeof($deny)==0 || !in_array($t, $deny))) {
        		$result[] = $entry;
    	      }
	    }
	  }

      
      // Add DB entries
      if ($dbs) {
          $dbCond = $this->getDbCond($dbs);
          // Construct the query
          foreach($allow as &$v) $v = '"' . $wpdb->escape($v) . '"';
          $allowCond = $allow ? "and entrytype in (" . implode(",",$allow) . ")" : "";
          foreach($deny as &$v) $v = '"' . $wpdb->escape($v) . '"';
          $denyCond = $deny ? "and entrytype not in (" . implode(",",$deny) . ")" : "";
          
          $st = "SELECT data FROM $papercite_table_name WHERE $dbCond $denyCond $allowCond";
          $rows = $wpdb->get_col($st);
          if ($rows) foreach($rows as $data) {
              $result[] = maybe_unserialize($data);
          }
      }
  }
  
      
      return  $this->showEntries($result, $tplOptions, false, $options["bibtex_template"], $options["format"], "bibtex");

	// bibshow / bibcite commands
    case "bibshow":
     $data = $this->getData($options["file"]);
      if (!$data) return "<span style='color: red'>[Could not find the bibliography file(s)".
          (current_user_can("edit_post") ? " with name [".htmlspecialchars($options["file"])."]" : "") ."</span>";

      // TODO: replace this by a method call
      $refs = array("__DB__" => Array());
      foreach($data as $bib => &$outer) {
          // If we have a database backend for a bibtex, use it
          if ($outer == "__DB__") 
              array_push($refs["__DB__"], $bib);
          else
        	foreach($outer as &$entry) {
        	  $key = $entry["cite"];
        	  $refs[$key] = &$entry;
        	}
      }

      $this->bibshow_tpl_options[] = $tplOptions;
      $this->bibshow_options[] = $options;
      array_push($this->bibshows, $refs);
      $this->cites[] = array();
      break;

      // Just cite
    case "bibcite":
      if (sizeof($this->bibshows) == 0) {
        if ($options["auto_bibshow"]) {
          // Automatically insert [bibshow] because of unexpected [bibcite]
          $generated_bibshow = array('[bibshow]', 'bibshow');
          $this->process($generated_bibshow);
          unset($generated_bibshow);
        } else {
          return "[<span title=\"Unknown reference: $options[key]\">?</span>]";
        }
      }

      $keys = preg_split("/,/",$options["key"]);
      $cites = &$this->cites[sizeof($this->cites)-1];      
      $returns = "";

      foreach($keys as $key) {
    	if ($returns) $returns .= ", ";

    	// First, get the corresponding entry
    	$num = $cites[$key];

    	  // Did we already cite this?
    	  if (!$num) {
    	    // no, register this
    	    $id = "BIBCITE%%" . $this->citesCounter . "%";
    	    $this->citesCounter++;
    	    $num = sizeof($cites);
    	    $cites[$key] = array($num, $id);
    	  } else {
    	    // yes, just copy the id
    	    $id =  $num[1];
    	  }
    	  $returns .= "$id";

      }

      return "[$returns]";

    case "/bibshow":
      return $this->end_bibshow();

    default:
      return "[error in papercite: unhandled]";
    }
  }

  //! Get a db condition subquery
  function getDbCond(&$dbArray) {
      global $wpdb;
      
      $dbs = array();
      foreach($dbArray as &$db)
          $dbs[] = "\"" . $wpdb->escape($db) . "\"";
      $dbs = implode(",", $dbs);
      if ($dbs) $dbs = "url in ($dbs)";
      
      return $dbs;
  }

  function end_bibshow() {
      global $wpdb, $papercite_table_name;
      
    // select from cites
    if (sizeof($this->bibshows) == 0) return "";
    // Remove the array from the stack
    $data = &array_pop($this->bibshows);
    $cites = &array_pop($this->cites);
    $tplOptions = &array_pop($this->bibshow_tpl_options);
    $options = &array_pop($this->bibshow_options);
    $refs = array();

    $dbs = papercite::getDbCond($data["__DB__"]);
    

    // Order the citations according to citation order
    // (might be re-ordered latter)
    foreach($cites as $key => &$cite) {
        // Search
        if (!$data[$key] && $dbs) {
            $val = $wpdb->get_var($wpdb->prepare("SELECT data FROM $papercite_table_name WHERE $dbs and bibtexid=%s", $key));
            if ($val !== FALSE) {               
                $refs[$cite[0]] = maybe_unserialize($val);
            }
        } else 
            $refs[$cite[0]] = $data[$key];
        
    	$refs[$cite[0]]["pKey"] = $cite[1];
    	// just in case
    	$refs[$cite[0]]["cite"] = $key;
    }
    
    ksort($refs);
    return $this->showEntries(array_values($refs), $tplOptions, true, $options["bibshow_template"], $options["format"], "bibshow");
  }

  /**
   * Show a set of entries
   * @param refs An array of references
   * @param options The options to pass to bib2tpl
   * @param getKeys Keep track of the keys for a final substitution
   */
  function showEntries(&$refs, &$options, $getKeys, $mainTpl, $formatTpl, $mode) {
    // Get the template files
    $mainFile = papercite::getDataFile("/tpl/$mainTpl.tpl");
    $formatFile = papercite::getDataFile("/format/$formatTpl.tpl");

    // Fallback to defaults if needed
    if (!$mainFile)
      $mainFile = papercite::getDataFile("/tpl/" .papercite::$default_options["${mode}_template"] .".tpl");
    if (!$formatFile)
      $formatFile = papercite::getDataFile("/format/" .papercite::$default_options["format"] . ".tpl");

    $main = file_get_contents($mainFile[0]);
    $format = file_get_contents($formatFile[0]);

    $bibtexEntryTemplate = new BibtexEntryFormat($format);

    // Gives a distinct ID to each publication (i.e. to display the corresponding bibtex)
    // in the reference list
    foreach($refs as &$entry) {
      $entry["papercite_id"] = $this->counter++;
    }

    // Convert (also set the citation key)
    $bib2tpl = new BibtexConverter($options, $main, $bibtexEntryTemplate);
    $bib2tpl->setGlobal("WP_PLUGIN_URL", WP_PLUGIN_URL);
    $r =  $bib2tpl->display($refs);

    // If we need to get the citation key back
    if ($getKeys) {
      foreach($refs as &$group)
    	foreach($group as &$ref) {
    	  $this->keys[] = $ref["pKey"];
    	  $this->keyValues[] = $ref["key"];
    	}
    }

    // Process text in order to avoid some unexpected WordPress formatting 
    return str_replace("\t", '  ', trim($r["text"]));
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
    wp_register_script('papercite', get_bloginfo('wpurl') . '/wp-content/plugins/papercite/js/papercite.js', array('jquery'));
    wp_enqueue_script('papercite');
  } 

  // Register and enqueue the stylesheet
  wp_register_style('papercite_css', WP_PLUGIN_URL . '/papercite/papercite.css' );
  wp_enqueue_style('papercite_css');

  // Initialise the object
  $papercite = new Papercite();
}

// --- Callback function ----
function &papercite_cb($myContent) {
  // Init
  $papercite = &$GLOBALS["papercite"];
  
  // Fixes issue #39 (maintenance mode support)
  if(!is_object($papercite))
      return $myContent;
  
  $papercite->init();
  
  // Database support if needed
  if ($papercite->options["use_db"]) {
      require_once(dirname(__FILE__) . "/papercite_db.php");
  }
    
  //  print "<div style='border: 1pt solid blue'>";  print(nl2br(htmlentities($myContent)));  print "</div>";

  // (1) First phase - handles everything but bibcite keys
  $text = preg_replace_callback("/\[\s*((?:\/)bibshow|bibshow|bibcite|bibtex)(?:\s+([^[]+))?]/",
				array($papercite, "process"), $myContent);

  // (2) Handles missing bibshow tags
  while (sizeof($papercite->bibshows) > 0)
    $text .= $papercite->end_bibshow();


  // (3) Handles custom keys in bibshow and return
  $text = str_replace($papercite->keys, $papercite->keyValues, $text);

  //  print "<div style='border: 1pt solid black'>";  print(nl2br(htmlentities($text)));  print "</div>";
  return $text;
}

// --- Add the documentation link in the plugin list
function papercite_row_cb($data, $file) {
  if ($file == "papercite/papercite.php") {
    $data[] = "<a href='" . WP_PLUGIN_URL . "/papercite/documentation/index.html'>Documentation</a>";
  }
  return $data;
}
add_filter('plugin_row_meta', 'papercite_row_cb',1,2);

// --- Add the different handlers to WordPress ---
add_action('init', 'papercite_init');	
add_action('wp_head', 'papercite_head');
add_filter('the_content', 'papercite_cb', -1);


?>
