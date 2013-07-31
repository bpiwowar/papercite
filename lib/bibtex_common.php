<?php

require_once 'PARSECREATORS.php';

/**
 * A list of creators (e.g., authors, editors)
 */
class BibtexCreators {
  function BibtexCreators(&$creators) {
    $this->creators = &$creators;
  }
  function count() {
    return sizeof($creators);
  }
  
  static function parse($authors) {
      $parseCreators = new PARSECREATORS();
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
      return new BibtexCreators($creators);
  }
  
}


?>