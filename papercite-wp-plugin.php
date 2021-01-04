<?php

/*
  Plugin Name: papercite
  Plugin URI: http://www.bpiwowar.net/papercite
  Description: papercite enables to add BibTeX entries formatted as HTML in wordpress pages and posts. The input data is the bibtex text file and the output is HTML. This fork adds the feature of textual footnotes, besides the references stored in bibtex files.
  Version: 0.5.18
  Author: Benjamin Piwowarski
  Author URI: http://www.bpiwowar.net
  Author: digfish
  Author URI: http://digfish.org
*/

// isolate papercite class in their own class file, keeping only the wordpress integtation
// in this file
require_once "papercite.classes.php";



// -------------------- Interface with WordPress


// --- Head of the HTML ----
function papercite_head()
{
    if (!function_exists('wp_enqueue_script')) {
      // In case there is no wp_enqueue_script function (WP < 2.6), we load the javascript ourselves
        echo "\n" . '<script src="'.  get_bloginfo('wpurl') . '/wp-content/plugins/papercite/js/jquery.js"  type="text/javascript"></script>' . "\n";
        echo '<script src="'.  get_bloginfo('wpurl') . '/wp-content/plugins/papercite/js/papercite.js"  type="text/javascript"></script>' . "\n";
    }
}

// --- Initialise papercite ---
function papercite_init()
{
    global $papercite;

    if (function_exists('wp_enqueue_script')) {
        wp_register_script('papercite', plugins_url('papercite/js/papercite.js'), array('jquery'));
        wp_enqueue_script('papercite');
    }

  // Register and enqueue the stylesheet
    wp_register_style('papercite_css', plugins_url('papercite/papercite.css'));
    wp_enqueue_style('papercite_css');

  // Initialise the object
    $papercite = new Papercite();
}

// --- Callback function ----
/**
 * @param $myContent
 *
 * @return mixed|string|string[]|null
 */
function &papercite_cb($myContent)
{
  // Init
    $papercite = &$GLOBALS["papercite"];
  
  // Fixes issue #39 (maintenance mode support)
    if (!is_object($papercite)) {
        return $myContent;
    }
  
    $papercite->init();
  
  // Database support if needed
    if ($papercite->options["use_db"]) {
        require_once(dirname(__FILE__) . "/papercite_db.php");
    }
    
  // (0) Skip processing on this page?
    if ($papercite->options['skip_for_post_lists'] && !is_single() && !is_page()) {
//        return preg_replace("/\[\s*((?:\/)bibshow|bibshow|bibcite|bibtex|ppcnote)(?:\s+([^[]+))?]/", '', $myContent);
        return preg_replace("/\[\s*((?:\/)bibshow|bibshow|bibcite|bibtex)(?:\s+([^[]+))?]/", '', $myContent);
    }

  // (1) First phase - handles everything but bibcite keys
    $text = preg_replace_callback(
 //       "/\[\s*((?:\/)bibshow|bibshow|bibcite|bibtex|bibfilter|ppcnote)(?:\s+([^[]+))?]/",
        "/\[\s*((?:\/)bibshow|bibshow|bibcite|bibtex|bibfilter)(?:\s+([^[]+))?]/",
        function($match) use($papercite) {
            return $papercite->process($match);
        },
        $myContent
    );

    //digfish: textual footnotes
/*    $note_matches = array();
    $note_matches_count = preg_match_all('/\[ppcnote\](.+?)\[\/ppcnote\]/i',$text,$note_matches);

    if ($note_matches_count !== FALSE) {
	    $ft_matches = $note_matches[1];
	    foreach ($ft_matches as $match) {
	        $papercite->textual_footnotes[] = $match;
	    }
    }*/

    $post_id = get_the_ID();

    $text = preg_replace_callback(
            '/\[ppcnote\](.+?)\[\/ppcnote\]/i',
            function($match) use($post_id,$papercite) {
                return $papercite->processTextualFootnotes($match,$post_id);
            },
        $text
    );

    if ( count($papercite->getTextualFootnotes() ) > 0) {
	    $text .= $papercite->showTextualFootnotes(get_the_ID());
    }

    // digfish: reset the footnotes after the end of post/page
    $papercite->textual_footnotes = array();
    $papercite->textual_footnotes_counter = 0;


  // (2) Handles missing bibshow tags
    while (sizeof($papercite->bibshows) > 0) {
        $text .= $papercite->end_bibshow();
    }

  // (3) Handles custom keys in bibshow and return
    $text = str_replace($papercite->keys, $papercite->keyValues, $text);

    return $text;
}

// --- Add the documentation link in the plugin list
function papercite_row_cb($data, $file)
{
    if ($file == "papercite/papercite-wp-plugin.php") {
        $data[] = "<a href='" . WP_PLUGIN_URL . "/papercite/documentation/index.html'>Documentation</a>";
    }
    return $data;
}
add_filter('plugin_row_meta', 'papercite_row_cb', 1, 2);

// --- Register the MIME type for Bibtex files
function papercite_mime_types($mime_types)
{
  // Adjust the $mime_types, which is an associative array where the key is extension and value is mime type.
    $mime_types['bib'] = 'application/x-bibtex'; // Adding bibtex
    return $mime_types;
}
add_filter('upload_mimes', 'papercite_mime_types', 1, 1);


/**
 * by digfish (09 Apr 2019)
 * Restore .bib upload functionality in Media Library for WordPress 4.9.9 and up
 * adapted from https://gist.github.com/rmpel/e1e2452ca06ab621fe061e0fde7ae150
 */
add_filter('wp_check_filetype_and_ext', function($values, $file, $filename, $mimes) {
    if ( extension_loaded( 'fileinfo' ) ) {
        // with the php-extension, a bib file is issues type text/plain so we fix that back to
        // application/x-bibtex by trusting the file extension.
        $finfo     = finfo_open( FILEINFO_MIME_TYPE );
        $real_mime = finfo_file( $finfo, $file );
        finfo_close( $finfo );
        if ( $real_mime === 'text/plain' && preg_match( '/\.(bib)$/i', $filename ) ) {
            $values['ext']  = 'bib';
            $values['type'] = 'application/x-bibtex';
        }
    } else {
        // without the php- extension, we probably don't have the issue at all, but just to be sure...
        if ( preg_match( '/\.(bib)$/i', $filename ) ) {
            $values['ext']  = 'bib';
            $values['type'] = 'application/x-bibtex';
        }
    }
    return $values;
}, PHP_INT_MAX, 4);

// --- Add the different handlers to WordPress ---
add_action('init', 'papercite_init');
add_action('wp_head', 'papercite_head');
add_filter('the_content', 'papercite_cb', -1);


?>
