<?php

// See http://wp-cli.org/blog/plugin-unit-tests.html

class ShowLinks extends WP_UnitTestCase {
    static $data = <<<EOF
@inproceedings{test,
    title="Hello world",
    author="B. Piwowarski"
}
EOF;

    public function setUp() {
        parent::setUp();
        $this->user_id = $this->factory->user->create();
    }

    function process_post($content) {
        $post_id = $this->factory->post->create( array( 
            'post_author' => $this->user_id, 
            'post_content' => $content
            ) 
        );

        add_post_meta($post_id, "papercite_data", ShowLinks::$data);
        $GLOBALS['post'] = $post_id;

        $doc = new DOMDocument();
        $processed = apply_filters('the_content', $content);

        $doc->loadHTML("$processed");

        return $doc;
    }

    function testOn() {
        $doc = $this->process_post("[bibshow file=custom://data show_links=1][bibcite key=test]");

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
        $doc = $this->process_post("[bibshow file=custom://data show_links=0][bibcite key=test]");

        $xpath = new DOMXpath($doc);        
        $href = $xpath->evaluate("//a[@class = 'papercite_bibcite']/@href");
        $this->assertEquals(0, $href->length, "There were {$href->length} links detected - expected none");
    }
}

