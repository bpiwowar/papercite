<?php

// See http://wp-cli.org/blog/plugin-unit-tests.html
require_once dirname(__FILE__) . '/common.inc.php';

class Issue95 extends PaperciteTestCase {
    static $data = <<<EOF
@inproceedings {179761,
author = {Radhika Mittal and Justine Sherry and Sylvia Ratnasamy and Scott Shenker},
title = {Recursively Cautious Congestion Control},
booktitle = {11th USENIX Symposium on Networked Systems Design and Implementation (NSDI 14)},
year = {2014},
month = Apr,
isbn = {978-1-931971-09-6},
address = {Seattle, WA},
pages = {373--385},
url = {https://www.usenix.org/conference/nsdi14/technical-sessions/presentation/mittal},
publisher = {USENIX Association},
}
EOF;



    function testOn() {
        $doc = $this->process_post("[bibtex file=custom://data process_titles=1]", Issue95::$data);

        $xpath = new DOMXpath($doc);        
        $title = $xpath->evaluate("//ul[@class = 'papercite_bibliography']//span[1]/text()");
        $this->assertTrue($title->length == 1, "There were {$title->length} span detected - expected 1");
        $title = $title->item(0)->textContent;

        $this->assertEquals($title, "11th usenix symposium on networked systems design and implementation (nsdi 14)");
    }

}

