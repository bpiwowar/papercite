<?php

// Common class for all tests

abstract class PaperciteTestCase extends WP_UnitTestCase {
    public function setUp() {
        parent::setUp();
        $this->user_id = $this->factory->user->create();
    }

    /** Create a post, process it and return the content */
    function process_post($content, $data) {
        $post_id = $this->factory->post->create( array( 
            'post_author' => $this->user_id, 
            'post_content' => $content
            ) 
        );

        add_post_meta($post_id, "papercite_data", $data);
        $GLOBALS['post'] = $post_id;

        $doc = new DOMDocument();
        $processed = apply_filters('the_content', $content);

        $doc->loadHTML("$processed");

        return $doc;
    }
}