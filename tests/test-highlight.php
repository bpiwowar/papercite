<?php

// See http://wp-cli.org/blog/plugin-unit-tests.html
require_once dirname(__FILE__) . '/common.inc.php';

class HighlightTest extends PaperciteTestCase {
    static $data = <<<EOF
@inproceedings{test,
    title="Hello world",
    author="B. Piwowarski"
}
EOF;



    function testHighlight() {
        $doc = $this->process_post("[bibtex file=custom://data highlight=\"Piwowarski\"]", HighlightTest::$data);
        print $doc->saveXML();

        $xpath = new DOMXpath($doc);   

        $highlight = $xpath->evaluate("//span[@class = 'papercite_highlight']/text()");
        $this->assertTrue($highlight->length == 1, "{$highlight->length} highlights detected - expected 1");
        $highlight = $highlight->item(0)->wholeText;

        $this->assertTrue($highlight == "Piwowarski", "The hilight [$highlight] is not as expected");
    }


}

