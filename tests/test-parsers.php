<?php

// See http://wp-cli.org/blog/plugin-unit-tests.html
require_once dirname(__FILE__) . '/common.inc.php';

class ParsersTest extends PaperciteTestCase {
    static $data = <<<EOF
@inproceedings{test,
    title="Hello",
    booktitle={{WORLD}},
    author="B. Piwowarski",
    year=2008
}
EOF;

    function setDown() {
        $GLOBALS["papercite"]->options["bibtex_parser"] = Papercite::$default_options["bibtex_parser"];
    }

    function testPear() {
        $this->process("pear");
    }

    function testOSBib() {
        $this->process("osbib");
    }

    function process($parser) {
        $GLOBALS["papercite"]->options["bibtex_parser"] = $parser;
        $doc = $this->process_post("[bibtex file=custom://data bibtex_template=custom://template]", [
            "data" => self::$data,
            "template" => self::$SIMPLE_TEMPLATE
        ]);

        $xpath = new DOMXpath($doc);   
        $text = trim($xpath->evaluate("string(//div[@class='entry'])"));

        $this->assertEquals($text, "B. Piwowarski, “Hello,” in WORLD,  2008.");
    }


}

