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

function readResource(&$formats, $xml, $bibtexMap) {
  $type = $xml->getAttribute("name");
  $bibtexType = $bibtexMap->types[$type];

  $depth = $xml->depth;
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
	$data .= $xml->readString();
      }

      if (!array_key_exists($xml->name, $bibtexMap->$type))
	$xml->skipToEnd();
      else {
	$types = &$bibtexMap->$type;
	$name = $types[$xml->name];
	$fieldDepth = $xml->depth;
	$pre = "";
	$post = "";
	while ($xml->levelRead($fieldDepth))  {
	  if ($xml->nodeType == XMLReader::ELEMENT) {
	    switch($xml->name) {
	    case "pre": $pre = processShortcodes($xml->readString()); break;
	    case "post": $post = processShortcodes($xml->readString()); break;
	    }
	    $xml->skiptoEnd();
	  }
	}
	$data .= "$pre@$name@$post";
      }
    } // end (element)
  }

  assert(!array_key_exists($type, $formats));
  $types = array();
  if ($bibtexType) $types[] = $bibtexType;
  $formats[$type] = array($types, $data);
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
    case "resource":
      readResource($formats, $xml, $bibtexMap);
      break;
    default:
      //msg("Skip [$xml->name]\n");
  }
};

// Print

print "<formats>\n";
foreach($formats as $key => &$array) {
  if (sizeof($array[0]) > 0) {
    print "<format types=\"" . implode(" ", $array[0]) . "\">\n";
    print $array[1];
    print "\n</format>\n\n";
  }
}
print "</formats>\n";

?>
