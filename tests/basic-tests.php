<?php

// See http://wp-cli.org/blog/plugin-unit-tests.html

class SampleTest extends WP_UnitTestCase {

    function testSample() {
        $user_id = $this->factory->user->create();
        $content = "[bibtex file=custom://data key=test]";
        $post_id = $this->factory->post->create( array( 
            'post_author' => $user_id, 
            'post_content' => $content
            ) 
        );

        $data = <<<EOF
@inproceedings{test,
    title="Hello world",
    author="B. Piwowarski"
}
EOF;
        add_post_meta($post_id, "papercite_data", $data);
        $GLOBALS['post'] = $post_id;

        $doc = new DOMDocument();
        $processed = apply_filters('the_content', $content);
        $doc->loadXML("$processed");
        $xpath = new DOMXpath($doc);        
        $title = trim($xpath->evaluate("string(//li/text()[1])"));

        // print $doc->saveXML();

        $this->assertStringStartsWith("B. Piwowarski, “Hello world.”", $title);
    }
}
