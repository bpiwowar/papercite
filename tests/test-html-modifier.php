<?php

// See http://wp-cli.org/blog/plugin-unit-tests.html
require_once dirname(__FILE__) . '/common.inc.php';

class HTMLModifierTest extends PaperciteTestCase {
    static $data = <<<EOF
@inproceedings{test,
    title="Hello world",
    author="B. Piwowarski",
    abstract="<b class=\"hello\">This is the abstract</b>"
}
EOF;

    static function getTemplate($modifier) {
    return <<<EOF
@{group@
@{entry@
<div id="abstract">@abstract$modifier@</div>
@}entry@
@}group@
EOF;
    }

    function check($modifier, $expected) {
        $doc = $this->process_post("[bibtex file=custom://data template=custom://template]", 
            array("data" => HTMLModifierTest::$data, "template" => HTMLModifierTest::getTemplate($modifier))
        );

        $xpath = new DOMXpath($doc);        
        $result = $xpath->evaluate("//div[@id = 'abstract']/node()");
        $text = "";
        for($i = 0; $i < $result->length; $i++) {
            $text .= $doc->saveXML($result->item($i));
        }
        $this->assertEquals($text, $expected, "Error with modifier $modifier");
    }

    function testDefault() {
        $this->check("", "<b class=\"hello\">This is the abstract</b>");
    }
    function testHTML() {
        $this->check(":html", "<b class=\"hello\">This is the abstract</b>");
    }
    function testStrip() {
        $this->check(":strip", "This is the abstract");
    }
    function testSanitize() {
        $this->check(":sanitize", "this-is-the-abstract");
    }
}

