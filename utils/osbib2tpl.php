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

function readResource(&$formats, $xml, $bibtexMap) {
  $type = $xml->getAttribute("name");
  $bibtexType = $bibtexMap->types[$type];

  $depth = $xml->depth;
  $data = array();
  while ($xml->levelRead($depth)) {
  
    // Got an element
    if ($xml->nodeType == XMLReader::ELEMENT) {
      if ($xml->name == "fallbackstyle") {
	$otherType = $xml->readString();
	assert(array_key_exists($otherType, $formats));
	if ($bibtexType) $formats[$otherType][0][] = $bibtexType;
	$xml->skipToEnd();
	while ($xml->levelRead($depth)) {}
	return;
      }
	
      if ($xml->name == "ultimate") {
	$data[] = array("", $xml->readString());
      } 
      else if ($xml->name == "independent") {
	$xml->skipToEnd();
      } else if (!array_key_exists($xml->name, $bibtexMap->$type)) {
	msg("Cannot translate $xml->name in $type\n");
	$data[] = array("","");
	$xml->skipToEnd();
      } else {
	$types = &$bibtexMap->$type;
	$name = $types[$xml->name];
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
	$data[] = array($name, $values);
      }
    } // end (element)
  }

  // Deals with the special OSBib fields
  // and concatenate the bib2tpl string
  $text = "";
  for($i = 0; $i < sizeof($data); $i++) {
    $row = &$data[$i];
    $name = $row[0];
    if ($name) {
      $options = &$row[1];
      $field = "@?$name@$options[pre]@$name@$options[post]@;$name@";
      $field = preg_replace("/__SINGULAR_PLURAL__/", "@?#$name>1@$options[plural]@:$name@$options[singular]@;$name@", $field);
      
      $next = $i+1 < sizeof($data) ?  $data[$i+1][0] : false;
      if ($next) {
	$field = preg_replace("/__DEPENDENT_ON_NEXT_FIELD__/", "@?$next@$options[dependentPost]@:@$options[dependentPostAlternative]@;$next@", $field);
      } else 	
	$field = preg_replace("/__DEPENDENT_ON_NEXT_FIELD__/", "$options[dependentPostAlternative]", $field);

      $previous = $i > 0 ?  $data[$i-1][0] : false;
      if ($previous) {
	$field = preg_replace("/__DEPENDENT_ON_PREVIOUS_FIELD__/", "@?$next@$options[dependentPre]@:@$options[dependentPreAlternative]@;$next@", $field);
      } else 	
	$previous = preg_replace("/__DEPENDENT_ON_PREVIOUS_FIELD__/", "$options[dependentPreAlternative]", $field);


      $text .= $field;
    } else 
      $text .= $row[1];
  }

  // Finish by filling up the structure
  assert(!array_key_exists($type, $formats));
  $types = array();
  if ($bibtexType) $types[] = $bibtexType;
  $formats[$type] = array($types, $text);
  if ($bibtexType == "article")
    $formats[$type][0][] = "#";
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

// Print


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
