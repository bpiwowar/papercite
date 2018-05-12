<?php

// Common class for all tests

abstract class PaperciteTestCase extends WP_UnitTestCase {
    static $SIMPLE_TEMPLATE = <<<EOF
        @{group@
        <div id="bibliography">
         @{entry@  <div class="entry">@#entry@</div>END @}entry@ </div>
        @}group@
EOF;

    public function setUp() {
        parent::setUp();
        $this->user_id = $this->factory->user->create();
    }

    static function domListToText($doc, $result) {
        $s = "";
        foreach($result as $node) {
            $s .= $doc->saveXML($node);
        }       
        return trim($s); 
    }

    /** 
     * Create a post, process it and return the content 
     * 
     * @param $content The post content
     * @param $data The post data or an array whose keys are the names of the post datas
     */
    function process_post($content, $data = null) {
        $post_id = $this->factory->post->create( array( 
            'post_author' => $this->user_id, 
            'post_content' => $content
            ) 
        );

        if ($data !== null) {
            if (is_array($data)) {
                foreach($data as $key => &$value) {
                    add_post_meta($post_id, "papercite_$key", $value);
                }
            } else {
                add_post_meta($post_id, "papercite_data", $data);
            }
        }
        $GLOBALS['post'] = $post_id;

        $doc = new DOMDocument();
        $processed = apply_filters('the_content', $content);
        try {
            $doc->loadHTML("$processed");
        } catch(\Exception $e) {
            $this->assertTrue(false, "Could not parse produced HTML [$e]: $processed");
        }

        return $doc;
    }
} 