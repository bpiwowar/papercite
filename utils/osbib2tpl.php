<?php

/**
 * Converts a OSBib format template to bib2tpl
 * (c) B. Piwowarski, 2011
 */

$xmlfile = $argv[1];

function msg($message) {
  fputs(STDERR, $message);
}

msg("Processing $xmlfile\n");

/**
 * Defines some useful commands
 */
class XMLPull extends XMLReader {
  /**
   * Skip to the end of the element
   */
  function skipToEnd() {
    assert($this->nodeType == XMLReader::ELEMENT);
    $depth = $this->depth;
    while ($this->read()) {
      if ($this->nodeType == XMLReader::END_ELEMENT && $this->depth == $depth)
	break; 
    }
  }

  /**
   * Read 
   */
  function levelRead($depth) {
    if (!$this->read()) 
      return false;
    if ($this->nodeType == XMLReader::END_ELEMENT && $this->depth == $depth)
      return false;
    return true;
  }

}



$xml = new XMLPull();
$xml->open($xmlfile);

require("STYLEMAPBIBTEX.php");
$bibtexMap = new STYLEMAPBIBTEX();

function processShortcodes($string) {
  return str_replace(array("[i]", "[/i]"), array("<span style=\"font-style: italic\">", "</span>"), $string);
}

function xmlspecialchars($text) {
  return str_replace('&#039;', '&apos;', htmlspecialchars($text, ENT_QUOTES));
}

function readCommon($xml) {
  $depth = $xml->depth;
  $text = "";
  while ($xml->levelRead($depth)) {
    if ($xml->nodeType == XMLReader::ELEMENT) {
      $text .= "<property name=\"$xml->name\" value=\"" . xmlspecialchars($xml->readString()) . "\"/>\n";
    }
  }
  return $text;
}

/** Get a value from an array or return an empty string
 */
function get(&$array, $key) {
  if (isset($array[$key])) return $array[$key];
  return "";
}

function readResource(&$formats, $xml, $bibtexMap) {
  $type = $xml->getAttribute("name");
  $bibtexTypes = $bibtexMap->types[$type];
  if (!is_array($bibtexTypes))
    $bibtexTypes = $bibtexTypes ? array($bibtexTypes) : array();
  else {
    foreach($bibtexTypes as &$bibtexType) {
      if (is_array($bibtexType)) {
	$bibtexTypeValues[$bibtexType[0]] = $bibtexType[1];
	$bibtexType = $bibtexType[0];
      }
      unset($bibtexType);
    }
  }

  $depth = $xml->depth;
  $data = array();
  while ($xml->levelRead($depth)) {
  
    // Got an element
    if ($xml->nodeType == XMLReader::ELEMENT) {
      if ($xml->name == "fallbackstyle") {
	$otherType = $xml->readString();
	assert(array_key_exists($otherType, $formats));
	foreach($bibtexTypes as $bibtexType)
	  $formats[$otherType][0][] = $bibtexType;
	$xml->skipToEnd();
	while ($xml->levelRead($depth)) {}
	return;
      }
	
      if ($xml->name == "ultimate") {
	$data[] = array("options" => array("indPost" => $xml->readString()));
      } 
      else if ($xml->name == "independent") {
	$cDepth = $xml->depth;
	$matches = array();
	while ($xml->levelRead($cDepth)) {
	  if ($xml->nodeType == XMLReader::ELEMENT && preg_match("/^independent_(\d)+$/",$xml->name,$matches)) {
	    $independent[$matches[1]] = $xml->readString();
	  }
	}
      } else {
	$types = &$bibtexMap->$type;
	$condition = "";
	$value = "";
	$name = "";
	if (!array_key_exists($xml->name, $types)) {
	  foreach($bibtexTypes as $bibtexType) {
	    if (isset($bibtexTypeValues[$bibtexType][$xml->name])) {
	      $typeValue = $bibtexTypeValues[$bibtexType][$xml->name];
	      msg("Field [$xml->name] equals [$typeValue] for [$bibtexType]\n");
	      $value .= "@?entrytype=${bibtexType}@${typeValue}@;@";
	      $condition .= ($condition ? "||" : "") . "entrytype=$bibtexType";
	    }
	  }
	} else {
	  $name = $types[$xml->name];
	  $value = "@$name@";
	  $condition = $name;
	}

	if (!$condition) {
	  msg("Cannot handle field [$xml->name] for [$type]\n");
	  $data[] = array();
	  $xml->skipToEnd();
	} else {
	  $fieldDepth = $xml->depth;
	  $pre = "";
	  $post = "";
	  $values = array();
	  while ($xml->levelRead($fieldDepth))  {
	    if ($xml->nodeType == XMLReader::ELEMENT) {
	      $values[$xml->name] = processShortcodes($xml->readString());
	      $xml->skiptoEnd();
	    }
	  }
	  $data[] = array("value" => $value, "options" => $values, "condition" => $condition, "name" => $name);
	}
      }
    } // end (element)
  }

  // $data contains the different fields, we have to post-process this
  // following OSBib formatting strategy

  /**
   * Check for independent characters.  These (should) come in pairs.
   * (adapted from OSBib - format/BIBFORMAT.php)
   */		
  if(isset($independent))
    {
      $independentKeys = array_keys($independent);
      while($independent)
	{
	  $preAlternative = $postAlternative = FALSE;
	  $startFound = $endFound = FALSE;
	  $pre = array_shift($independent);
	  $post = array_shift($independent);
	  if(preg_match("/%(.*)%(.*)%|%(.*)%/U", $pre, $dependent))
	    {
	      if(sizeof($dependent) == 4)
		$pre = $dependent[3];
	      else
		{
		  $pre = $dependent[1];
		  $preAlternative = $dependent[2];
		}
	    }
	  if(preg_match("/%(.*)%(.*)%|%(.*)%/U", $post, $dependent))
	    {
	      if(sizeof($dependent) == 4)
		$post = $dependent[3];
	      else
		{
		  $post = $dependent[1];
		  $postAlternative = $dependent[2];
		}
	    }
	  /**
	   * Strip backticks used in template
	   */
	  $preAlternative = str_replace("`", '', $preAlternative);
	  $postAlternative = str_replace("`", '', $postAlternative);
	  $firstKey = array_shift($independentKeys);
	  $secondKey = array_shift($independentKeys);
	  
	  $condition = "";
	  $min = $secondKey + 1;
	  $max = $firstKey;
	  for($index = $firstKey; $index <= $secondKey; $index++) {
	    $rowCondition = get($data[$index], "condition");
	    if ($rowCondition) {
	      $min = min($min, $index);
	      $max = max($max, $index);
	      $condition .= ($condition?"||":"") . $rowCondition;
	    }
	  }

	  if ($min > $secondKey) {
	    msg("Could not handle independent $firstKey to $secondKey for type $type"); 
	    continue;
	  }

	  //msg("Independent ($firstKey, $secondKey): @?$condition@$pre@:@$preAlternative@; AND @?$condition@$post@:@$postAlternative@;@\n");
	  $data[$firstKey]["options"]["indPre"] =  "@?${condition}@${pre}@:@${preAlternative}@;@";
	  $data[$secondKey]["options"]["indPost"] = "@?${condition}@${post}@:@${postAlternative}@;@";
	}
    }

  // Deals with the special OSBib fields
  // and concatenate the bib2tpl string
  $text = "";
  for($i = 0; $i < sizeof($data); $i++) {
    $row = &$data[$i];
    $value = get($row, "value");
    $options = &get($row, "options");
    $condition = &get($row, "condition");
    $name = get($row, "name");

    $text .= get($options, "indPre");

    if ($condition) {
      $field = "@?$condition@$options[pre]${value}$options[post]@;@";
      if ($name)
	$field = preg_replace("/__SINGULAR_PLURAL__/", "@?#$name>1@$options[plural]@:$name@$options[singular]@;$name@", $field);
      else 
      	$field = preg_replace("/__SINGULAR_PLURAL__/", "$options[singular]", $field);

      $next = $i+1 < sizeof($data) ?  get($data[$i+1], "condition") : false;
      if ($next) {
	$field = preg_replace("/__DEPENDENT_ON_NEXT_FIELD__/", "@?$next@" . get($options, "dependentPost") . "@:@$options[dependentPostAlternative]@;$next@", $field);
      } else 	
	$field = preg_replace("/__DEPENDENT_ON_NEXT_FIELD__/", "$options[dependentPostAlternative]", $field);

      $previous = $i > 0 ?  get($data[$i-1], "condition") : false;
      if ($previous) {
	$field = preg_replace("/__DEPENDENT_ON_PREVIOUS_FIELD__/", "@?$next@" . get($options, "dependentPre") . "@:@$options[dependentPreAlternative]@;$next@", $field);
      } else 	
	$field = preg_replace("/__DEPENDENT_ON_PREVIOUS_FIELD__/", "$options[dependentPreAlternative]", $field);


      $text .= $field;
    }   

    $text .= get($options, "indPost");

  }



  // Finish by filling up the structure
  if (in_array("article", $bibtexTypes))
    $bibtexTypes[] = "#";
  $formats[$type] = array($bibtexTypes, htmlentities($text));
}

$formats = array();
while ($xml->read()) {
  if ($xml->nodeType == XMLReader::ELEMENT)
    switch($xml->name) {
    case "osbibVersion":
      $v = $xml->readString();
      if ($v != "2.0") throw new Exception("Cannot read OSBib version ${version}");
      break;
    case "common":
      $commonXML = readCommon($xml);
      break;
    case "resource":
      readResource($formats, $xml, $bibtexMap);
      break;
    default:
      //msg("Skip [$xml->name]\n");
    }
};



print "<formats>\n";
print "\n";
print $commonXML;
print "\n";
foreach($formats as $key => &$array) {
  if (sizeof($array[0]) > 0) {
    print "<format types=\"" . implode(" ", $array[0]) . "\">\n";
    print $array[1];
    print "\n</format>\n\n";
  }
}
print "</formats>\n";

?>
