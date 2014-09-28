<?php

// See http://wp-cli.org/blog/plugin-unit-tests.html
require_once dirname(__FILE__) . '/common.inc.php';

class ShowLinks extends PaperciteTestCase {
    static $data = <<<EOF
@inproceedings{test,
    title="Hello world",
    author="B. Piwowarski"
}
EOF;



    function testOn() {
        $doc = $this->process_post("[bibshow file=custom://data show_links=1][bibcite key=test]", ShowLinks::$data);

        $xpath = new DOMXpath($doc);        
        $href = $xpath->evaluate("//a[@class = 'papercite_bibcite']/@href");
        $this->assertTrue($href->length == 1, "There were {$href->length} links detected - expected 1");
        $href = $href->item(0)->value;

        $id = $xpath->evaluate("//div[@class = 'papercite_entry']/@id");
        $this->assertTrue($id->length == 1, "There were {$id->length} entries detected - expected 1");
        $id = $id->item(0)->value;

        $this->assertTrue($href == "#$id", "The href [$href] do not match the ID [$id] for show_links");
    }

    function testOff() {
        $doc = $this->process_post("[bibshow file=custom://data show_links=0][bibcite key=test]", ShowLinks::$data);

        $xpath = new DOMXpath($doc);        
        $href = $xpath->evaluate("//a[@class = 'papercite_bibcite']/@href");
        $this->assertEquals(0, $href->length, "There were {$href->length} links detected - expected none");
    }
}

