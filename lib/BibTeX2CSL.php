<?php
/*  BibTeX2CSL.php

    Converts a BibTeX entry into a CSL entry
    
    Copyright 2012  Benjamin Piwowarski  (email : benjamim@bpiwowar.net)

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

class BibTeX2CSL {

    // From http://www.docear.org/2012/08/08/docear4word-mapping-bibtex-fields-and-types-with-the-citation-style-language/
    static $entryMap = array(
        "article" => "article", 

        "proceedings" => "book",
        "book" => "book", 
        "manual" => "book",
        "periodical" => "book",
 
        "booklet" => "pamphlet", 

        "inbook" => "chapter",
        "incollection" => "chapter",

        "mastersthesis" => "thesis",
        "phdthesis" => "thesis",

        "techreport" => "report",

        "patent" => "patent",

        "electronic" => "webpage",

        "misc" => "article",
        "other" => "article",

        "standard" => "legislation",

        "unpublished" => "manuscript"
    );

    // Map from CSL field to one or more bibtex fields
    // If the mapped value is a:
    // - a string: direct mapping
    // - an array of arrays ($types, key_true, key_false): if bibtex type is in $types,
    // maps from $key_true, otherwise from $key_false. If the $key_xxx is null,
    // then tries 
    static $fieldMap = array(
        "publisher-place" => "address",

        "event-place" => "location",

        "author" => "author",

        "editor" => "editor", // contained-editor, collection-editor

        "edition" => "edition",

        "publisher" => array(
            array(array("techreport"), "institution"),
            array(array("thesis"), "school"), 
            "institution", "organization"),

        "title" => array(
            array(array("inbook"), "chapter", "title"), 
        ),

        "doi" => "doi",

        "note" => "note",
        "annote" => "annote",
        "keywords" => "keyword",
        "status" => "status",
        "accessed" => "accessed",

        "volume" => "volume",
        "number" => "issue", // number

        "pages" => "page",

        "url" => "url"
    );

    function import(&$key, &$dest_key) {
        if (array_key_exists($key, $this->bibtex)) {
            $value = $this->bibtex[$key];

            if (is_object($value)) {
                $this->entry[$dest_key] = $value->toCSL();
                return true;
            } 

            $this->entry[$dest_key] = &$value;
            return true;
        }
        return false;
    }

    var $bibtex;
    var $entry;

    function __construct(&$bibtex) {
        $this->bibtex = &$bibtex;
        $this->entry = array();
    }

    static function convert(&$bibtex) {
        $csl = new BibTeX2CSL($bibtex);
        $type = $entryMap[$bibtex["entry-type"]];

        foreach(BibTeX2CSL::$fieldMap as $dest_key => $keys) {
            if (is_array($keys)) {
                foreach($keys as $from) {
                    if (is_array($from)) {
                        $key = array_search($type, $from[0]) ? $from[1] : $from[2];
                        if ($key && $csl->import($key, $dest_key))
                            break;
                    } 
                    else if ($csl->import($from, $dest_key)) break;
                }
            } 
            else $csl->import($keys, $dest_key);
        }
        
        return $csl->entry;

    }

}


?>