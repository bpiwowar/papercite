<?php

/*  Copyright 2012  Benjamin Piwowarski  (email : benjamim@bpiwowar.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*
  Documentation:
  - http://ottopress.com/2009/wordpress-settings-api-tutorial/ 
 */


add_action('admin_menu', 'papercite_create_menu');


function papercite_create_menu() {
  add_options_page('Custom Papercite Page', 'Papercite plug-in', 'manage_options', 'papercite', 'papercite_options_page');
}


function papercite_options_page() {
?>
  <div>
    <h2>Papercite options</h2>
    
    Options related to the papercite plugin.
    
    <form action="options.php" method="post">
    <?php settings_fields('papercite_options'); ?>
    <?php do_settings_sections('papercite'); ?>
    
    <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
    </form>
    </div>

<?php
}

// add the admin settings and such
add_action('admin_init', 'papercite_admin_init');
function papercite_admin_init(){
  $GLOBALS["papercite"]->init();

  register_setting( 'papercite_options', 'papercite_options', 'papercite_options_validate' );

  // Default settings
  add_settings_section('papercite_main', 'Defaults settings', 'papercite_section_text', 'papercite');
  add_settings_field('file', 'Default bibtex file', 'papercite_file', 'papercite', 'papercite_main');
  add_settings_field('format', 'Default format', 'papercite_format', 'papercite', 'papercite_main');
  add_settings_field('timeout', 'Default timeout to reload pages', 'papercite_timeout', 'papercite', 'papercite_main');

  add_settings_field('bibtex_template', 'Main bibtex template', 'papercite_bibtex_template', 'papercite', 'papercite_main');
  add_settings_field('bibshow_template', 'Main bibshow template', 'papercite_bibshow_template', 'papercite', 'papercite_main');

  add_settings_section('papercite_choices', 'Options', 'papercite_choices_text', 'papercite');
  add_settings_field('bibtex_parser', 'Bibtex parser', 'papercite_bibtex_parser', 'papercite', 'papercite_choices');
  add_settings_field('use_db', 'Database', 'papercite_use_db', 'papercite', 'papercite_choices');
}

function papercite_section_text() {
  echo '<p>Set the default settings - leave the fields empty to use papercite default values</p>';
} 


function papercite_choices_text() {
  echo '<p>Options to set how papercite process the data</p>';
} 



function papercite_file() {
  $options = $GLOBALS["papercite"]->options;
  echo "<input id='papercite_file' name='papercite_options[file]' size='40' type='text' value='{$options['file']}' />";
} 

function papercite_format() {
  $options = $GLOBALS["papercite"]->options;
  echo "<input id='papercite_format' name='papercite_options[format]' size='40' type='text' value='{$options['format']}' />";
} 

function papercite_timeout() {
  $options = $GLOBALS["papercite"]->options;
  echo "<input id='papercite_timeout' name='papercite_options[timeout]' size='40' type='text' value='{$options['timeout']}' />";
} 

function papercite_bibtex_template() {
  $options = $GLOBALS["papercite"]->options;
  echo "<input id='papercite_bibtex_template' name='papercite_options[bibtex_template]' size='40' type='text' value='{$options['bibtex_template']}' />";
}

function papercite_bibshow_template() {
  $options = $GLOBALS["papercite"]->options;
  echo "<input id='papercite_bibshow_template' name='papercite_options[bibshow_template]' size='40' type='text' value='{$options['bibshow_template']}' />";
} 

function papercite_deny() {
  $options = $GLOBALS["papercite"]->options;
  echo "<input id='papercite_deny' name='papercite_options[deny]' size='40' type='text' value='"
    . implode(" ", $options['deny']) . "' />";
} 


function papercite_bibtex_parser() {
  $option = $GLOBALS["papercite"]->options["bibtex_parser"];
  echo "<select id='papercite_bibtex_parser' name='papercite_options[bibtex_parser]'>";
  foreach(papercite::$bibtex_parsers as $key => $value) 
    print "<option value=\"$key\"" . ($key == $option ? ' selected="selected"' : "") . ">$value</option>";
  print "</select>";
} 

add_action('wp_ajax_papercite_create_db', 'papercite_ajax_callback');

function papercite_ajax_callback() {
    require_once(dirname(__FILE__) . "/papercite_db.php");
    print json_encode(papercite_install(true));
	die(); 
}

function papercite_use_db() {
  $option = $GLOBALS["papercite"]->options["use_db"];


  require_once(dirname(__FILE__) . "/papercite_db.php");
  global $papercite_table_name, $wpdb;

  $exists =  sizeof($wpdb->get_col("SHOW TABLES LIKE '$papercite_table_name'")) == 1;
 
  echo "<div>Papercite can use a database backend to avoid reparsing bibtex files and loading the full data each time<div>";
  print "<div id=\"papercite_db_ok\" style='" . ($exists ? "" : "display:none;"). "color:blue'>The database has been created.</div>";
  print "<div id=\"papercite_db_nok\" style='" .(!$exists ? "" : "display:none;"). ($option ? "color:red;" : ""). "'>The database does not exist. [<span class='papercite_link' id='papercite_create_db'>Create</span>]</div>";

  if ($exists) {
    // Display some information
    print "<div class='papercite_info'>" . $wpdb->get_var("SELECT count(*) FROM $papercite_table_name WHERE not URL like 'ts://%'") . " entries in the database</div>";
    print "<div class='papercite_info'>Cached bibtex files: " . 
      implode(", ", $wpdb->get_col("SELECT substr(URL,6) from $papercite_table_name WHERE URL like 'ts://%'")) . "</div>";
  }

  echo "<input type='radio' id='papercite_use_db' " . ($option ? " checked='checked' " : "") . " value='yes' name='papercite_options[use_db]' /> Yes ";
  echo "<input type='radio' id='papercite_use_db' " . (!$option ? " checked='checked' " : "") . "value='no' name='papercite_options[use_db]' /> No";
  
  wp_enqueue_script( 'json2' );
  wp_enqueue_script( 'jquery-ui-dialog' );
  ?>
  <script type="text/javascript" >
  jQuery("#papercite_create_db").click(function() {
  	var data = {
  		action: 'papercite_create_db'
  	};

  	jQuery.post(ajaxurl, data, function(response) {
  	    var r = JSON.parse(response);
  	    var d = jQuery("<div style='background:white; border: 1px solid black; padding: 3px; margin: 3px; '></div>");
  	    if (r[1] == "") {
  	      d.html("Table created").dialog({modal: true});
  	      jQuery("#papercite_db_nok").hide();
  	      jQuery("#papercite_db_ok").show();
        } else d.html(r[1]).dialog({modal: true});
  	});
  });
  </script>
  <?php
  
}

function papercite_set(&$options, &$input, $name) {
  if (array_key_exists($name, $input)) {
    $options[$name] = trim($input[$name]);
    if (!$options[$name]) 
      unset($options[$name]);
  }
}

function papercite_options_validate($input) {
  $options = get_option('papercite_options');

  $options['use_db'] = $input['use_db'] == "yes";
      
  $options['file'] = trim($input['file']);
  $options['timeout'] = trim($input["timeout"]);
  
  papercite_set($options, $input, "bibshow_template");
  papercite_set($options, $input, "bibtex_template");
  papercite_set($options, $input, "format");
  papercite_set($options, $input, "bibtex_parser");

  return $options;
}

?>
