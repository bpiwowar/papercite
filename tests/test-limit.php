<?php

// See http://wp-cli.org/blog/plugin-unit-tests.html

require_once dirname(__FILE__) . '/common.inc.php';

/**
 * Test the limit attribute
 */
class LimitTest extends PaperciteTestCase {
    function setDown() {
        $GLOBALS["papercite"]->options["bibtex_parser"] = Papercite::$default_options["bibtex_parser"];
    }

    function testLimit() {
        $data = "";
        for($i = 2000; $i < 2030; ++$i) {
            $data .= <<<EOF
            @inproceedings{test,
                title="Hello",
                booktitle={{WORLD}},
                author="B. Piwowarski",
                year=$i
            }    
EOF;
        }
        
        $doc = $this->process_post("[bibtex order=year limit=3 file=custom://data bibtex_template=custom://template]", [
            "data" => $data,
            "template" => self::$SIMPLE_TEMPLATE
        ]);

        $xpath = new DOMXpath($doc);   
        $text = trim($xpath->evaluate("count(//div[@class='entry'])"));

        $this->assertEquals($text, 3);
    }


}

