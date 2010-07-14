<?php

  /*
   Plugin Name: papercite
   Plugin URI: http://www.bpiwowar.net/papercite
   Description: papercite enables to add bibtex entries formatted as HTML in wordpress pages and posts. The input data is the bibtex text file and the output is HTML. 
   Version: 0.2.5
   Author: Benjamin Piwowarski
   Author URI: http://www.bpiwowar.net
  */


  /*  Copyright 2010 Benjamin Piwowarski (email: benjamin in the domain bpiwowar <DOT> net)


-----------------
Based on bib2html (version 0.9.3) of Sergio Andreozzi  (email : sergio <DOT> andreozzi <AT> gmail <DOT> com) 

This plug-in has been improved thanks to the suggestons and contributions of
- Cristiana Bolchini
-- cleaner bibtex presentation
- Patrick Maué
-- remote bibliographies managed by citeulike.org or bibsonomy.org
- Nemo
-- more characters on key
- Marco Loregian
-- inverting bibtex and html
-----------------


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



class Papercite {

  var $parse = false;

  var $cites = array();

  // List of publications for those citations
  var $bibshows = array();

  // Our caches (bibtex files and formats)
  var $cache = array();
  var $formats = array();

  /** Initialise the bibtex parser */
  function init() {
    $OSBiBPath = dirname(__FILE__) . '/OSBiB/';
    include_once($OSBiBPath.'format/bibtexParse/PARSEENTRIES.php');
    include_once($OSBiBPath.'format/BIBFORMAT.php');
    include_once(dirname(__FILE__) . '/class.TemplatePower.inc.php');
    $this->parse = NEW PARSEENTRIES();
    $this->parse->expandMacro = TRUE;
    $this->parse->fieldExtract = TRUE;
    $this->parse->removeDelimit = TRUE;
  }



  function getCiteFormat($type) {
    $OSBiBPath = dirname(__FILE__) . '/OSBiB/';
    include_once($OSBiBPath.'format/CITEFORMAT.php');
    $citeformat = new CITEFORMAT();
    list($info, $citation, $styleCommon, $styleTypes) = $citeformat->loadStyle("styles/bibliography/", "APA");
    $citeformat->getStyle($styleCommon, $styleTypes);
    return $citeformat;
  }

  function getFormat($type) {
    if (!$this->formats[$type]) {
      $OSBiBPath = dirname(__FILE__) . '/OSBiB/';
      $bibformat = NEW BIBFORMAT($OSBiBPath, TRUE); // TRUE implies that the input data is in bibtex format
      $bibformat->cleanEntry=TRUE; // convert BibTeX (and LaTeX) special characters to UTF-8
      list($info, $citation, $styleCommon, $styleTypes) = $bibformat->loadStyle($OSBiBPath."styles/bibliography/", $type);
      $bibformat->getStyle($styleCommon, $styleTypes);
      $this->formats[$type] = $bibformat;
    }
    return $this->formats[$type];
  }
    
  /* Returns filename of cached version of given url  */
  function getCached($url) {

    // check if cached file exists
    $name = strtolower(preg_replace("@[/:]@","_",$url));
    $dir = dirname(__FILE__) . "/cache";
    $file = "$dir/$name.bib";


    // check if file date exceeds 60 minutes   
    if (! (file_exists($file) && (filemtime($file) + 3600 > time())))  {
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
   Get the bibtex data from an URI
  */
  function getData($biburi) {
    if (!$this->cache[$biburi]) {
      if (strpos($biburi, "http://") === 0) 
	$bibFile = $this->getCached($biburi);
      else {
	$bibFile = dirname(__FILE__) . "/../papercite-data/bib/" . $biburi;
	if (!file_exists($bibFile)) 
	  $bibFile = dirname(__FILE__) . "/data/" . $biburi;
      }
      
      if (!file_exists($bibFile)) {
	return NULL;
      }
      
      if (file_exists($bibFile)) {
	$data = file_get_contents($bibFile);
	if (!empty($data)) {
	  $this->init();
	  $this->parse->loadBibtexString($data);
	  $this->parse->extractEntries();
	
	  $this->cache[$biburi] = $this->parse->returnArrays();
	}
      }

    }


    return $this->cache[$biburi];
  }
    
  function pdf($entry) {
    $id = strtolower(preg_replace("@[/:]@", "-", $entry["bibtexCitation"]));

    foreach(array("../papercite-data/pdf", "data") as $subfolder) {
      if (file_exists(dirname(__FILE__) . "/$subfolder/" . $id . ".pdf")) {
	return " <a href='" .  get_bloginfo('wpurl') . "/wp-content/plugins/$subfolder/" . $id . ".pdf" . "' title='Go to document'><img src='" . get_bloginfo('wpurl') . "/wp-content/plugins/papercite/pdf.png' width='10' height='10' alt='PDF' /></a>";
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
   * Handles a match in the post
   */
  function process(&$matches) {
    // Get the options
    $options_pairs = array();
    preg_match_all("/^(?:(\w+)=(\S+))(?:\s+(\w+)=(\S+))*$/", $matches[2], $options_pairs, PREG_SET_ORDER);
    $options_pairs = $options_pairs[0];
    for($i = sizeof($options_pairs)-2; $i >= 0; $i-=2) {
      $options[$options_pairs[$i]] =  $options_pairs[$i+1];
    }

    $command = $matches[1];

    switch($command) {
      // Outputs some bibtex entries (to remain compatible with bib2html)
    case "bibtex":
      $data = $this->getData($options["file"]);
      if (!$data) return;
      $entries = &$data[2];

      if (array_key_exists('key', $options)) {
	$keys = split(",", $options["key"]);
	$a = array();
	foreach($entries as $entry) {
	  if (in_array($entry["bibtexCitation"], $keys)) {
	    $a[$entry["bibtexCitation"]] = $entry;
	    break;
	  }
	}
	$entries = $a;
      } else {
	// First filter if needed
	$allow = $options["allow"];
	$deny = $options["deny"];
	if ($allow || $deny) {
	  $allow = $allow ? split(",",$allow) : false;
	  $deny =  $deny ? split(",", $deny) : false;

	  $entries2 = $entries;
	  $entries = array();
	  foreach($entries2 as &$entry) {
	    $t = $entry["bibtexEntryType"];
	    if ((!$allow || in_array($t, $allow)) && (!$deny || !in_array($t, $deny)))
	      $entries[$entry["bibtexCitation"]] = $entry;
	  }
	}
	    
	// Show everyting
	usort($entries, array($this, "sortByYear"));
	$reverse=true;
	if ($reverse) {
	  $entries = array_reverse($entries);
	}
      } 
      $refs = array();
      foreach($entries as &$ref) {
	$refs[$ref["bibtexCitation"]] = $ref;
      }

      return  $this->showEntries($refs);

      // Output bibtex for cited references
    case "bibshow":
      $data = $this->getData($options["file"]);
      if (!$data) return "<span style='color: red'>[Could not find the bibliography file(s)]</span>";

      $refs = array();
      foreach($data[2] as &$entry) {
	$key = $entry["bibtexCitation"];
	$refs[$key] = &$entry;
      }
      array_push($this->bibshows, &$refs);
      break;

      // Just cite
    case "bibcite":
      if (sizeof($this->bibshows) == 0) return "[?]";

      $key = $options["key"];
      $refs = &$this->bibshows[sizeof($this->bibshows)-1];
      if (array_key_exists($key, $refs)) {
	if (!($num = $this->cites[$key])) {
	  $num = sizeof($this->cites) + 1;
	  $this->cites[$key] = $num;
	}
	return "[" . $num . "]";
      }
      return "[<span title=\"Unkown reference: $key\">?</span>]";

    case "/bibshow":
      // select from cites
      if (sizeof($this->bibshows) == 0) return "";
      $data = &array_pop($this->bibshows);
      $refs = array();
      foreach($data as $key => &$entry) {
	$num = $this->cites[$key];
	if ($num) $refs[$num] = $entry;
      }
      ksort($refs);
      return $this->showEntries($refs);

    default:
      return "[error in papercite: unhandled]";
    }
  }


  function sortByYear($a, $b) {
    $f1 = $a['year']; 
    $f2 = $b['year']; 

    if ($f1 == $f2) return 0;

    return ($f1 < $f2) ? -1 : 1;
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


  function showEntries(&$refs) {
    static $counter = 0;

    $counter++;

#    print_r($refs);
    $tpl = new TemplatePower(dirname(__FILE__) . '/bibentry-html.tpl');
    $bibformat = $this->getFormat("IEEE");

    $tpl->prepare();
    foreach($refs as $key => &$entry) {
      $bibkey = $entry["bibtexCitation"];

      // Get the resource type ('book', 'article', 'inbook' etc.)
      $resourceType = $entry['bibtexEntryType'];
	
      //  adds all the resource elements automatically to the BIBFORMAT::item array
      $bibformat->preProcess($resourceType, $entry);
	
      // get the formatted resource string ready for printing to the web browser
      // the str_replace is used to remove the { } parentheses possibly present in title 
      // to enforce uppercase, TODO: check if it can be done only on title 
      $tpl->newBlock("bibtex_entry");
      $tpl->assign("pkey", "[" . $key. "]");
      $tpl->assign("year", $entry['year']);
	
      $tpl->assign("type", $entry['bibtexEntryType']);
      $tpl->assign("url", $this->toDownload($entry));
      $tpl->assign("pdf", $this->pdf($entry));
      $tpl->assign("key", $counter . "-" . strtr($bibkey, ":", "-"));

      $tpl->assign("entry", str_replace(array('{', '}'), '', $bibformat->map()));
      $tpl->assign("bibtex", $this->formatBibtex($entry['bibtexEntry']));
    }        
     
    return $tpl->getOutputContent();     
  }
				 }


// -------------------- Interface with WordPress


function papercite_cb($myContent) {
  return preg_replace_callback("/\[\s*((?:\/)bibshow|bibshow|bibcite|bibtex)(?:\s+([^[]+))?]/", array($GLOBALS["papercite"], "process"), $myContent);
}


function papercite_head()
{
  if (!function_exists('wp_enqueue_script')) {
    echo "\n" . '<script src="'.  get_bloginfo('wpurl') . '/wp-content/plugins/papercite/js/jquery.js"  type="text/javascript"></script>' . "\n";
    echo '<script src="'.  get_bloginfo('wpurl') . '/wp-content/plugins/papercite/js/papercite.js"  type="text/javascript"></script>' . "\n";
  }
  echo "<style type=\"text/css\">
div.bibtex {
    display: none;
}</style>";

}

function papercite_init() {
  if (function_exists('wp_enqueue_script')) {
    wp_register_script('papercite', get_bloginfo('wpurl') . '/wp-content/plugins/papercite/js/papercite.js', array('jquery'), '0.7');
    wp_enqueue_script('papercite');
  } 
  global $papercite;
  $papercite = new Papercite();
}

add_action('init', 'papercite_init');	
add_action('wp_head', 'papercite_head');
add_filter('the_content', 'papercite_cb',1);


?>
