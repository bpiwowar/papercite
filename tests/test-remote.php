<?php

require_once dirname(__FILE__) . '/common.inc.php';

class RemoteTest extends PaperciteTestCase {

    function testRemote() {
        $cachedir = dirname(dirname(__FILE__)) . "/cache";
        foreach(glob("$cachedir/*") as $file) {
            unlink($file);
        }

        $this->test();
    }

    function testRemoteCache() {
        $this->test();
    }

    function test() {
        $url = "https://gist.githubusercontent.com/bpiwowar/9793f4e2da48dfb34cde/raw/5fbff41218107aa9dcfab4fc53fe8e2b86ea8416/test.bib";
        $doc = $this->process_post("[bibtex file=$url]");

        $xpath = new DOMXpath($doc);   

        $items = $xpath->evaluate("//ul[@class = 'papercite_bibliography']/li");
        $this->assertTrue($items->length == 1, "{$items->length} items detected - expected 1");
        $text = $items->item(0)->textContent;

        $this->assertRegExp("#Piwowarski#", $text);
    }

}

