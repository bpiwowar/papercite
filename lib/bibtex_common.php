<?php

require_once 'PARSECREATORS.php';

/**
 * A list of creators (e.g., authors, editors)
 */
class PaperciteBibtexCreators {
  function PaperciteBibtexCreators(&$creators) {
    $this->creators = &$creators;
  }
  function count() {
    return sizeof($this->creators);
  }
  
  static function parse($authors) {
      $parseCreators = new PaperciteParseCreators();
      $creators = $parseCreators->parse($authors);
      foreach($creators as &$cArray) {
        $cArray = array(
  		      "surname" => trim($cArray[2]),
  		      "firstname" => trim($cArray[0]),
  		      "initials" => trim($cArray[1]),
  		      "prefix" => trim($cArray[3])
  		      );
        unset($cArray);
      }
      return new PaperciteBibtexCreators($creators);
  }
  
    function toCSL() {
        // dropping-particle, non-dropping-particle
        $authors = array();
        foreach($this->creators as $c) 
            $authors[] = array("given" => $c["firstname"], "family" => $c["surname"],
            "suffix" => $c["suffix"]);
        return $authors;
    }
}


/**
 * A page range
 */
class BibtexPages {
    function BibtexPages($start, $end) {
        $this->start = (int)$start;
        $this->end = (int)$end;
    }
    function toCSL($i) {
        $c = $this->count();
        if ($c == 1) return $this->start;
        return $this->start . "-" . $this->end;
    }

    function count() {
        return ($this->start ? 1 : 0) + ($this->end ? 1 : 0);
    }
}
?>