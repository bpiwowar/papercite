<?php

/*
  Documentation:
  - http://ottopress.com/2009/wordpress-settings-api-tutorial/ 
  - 
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

function papercite_use_db() {
  $option = $GLOBALS["papercite"]->options["use_db"];
  echo "<p>Papercite can use a database backend to avoid reparsing bibtex files and loading the full data each time<p>";
  echo "<input type='radio' id='papercite_use_db' " . ($option ? " checked='checked' " : "") . " value='yes' name='papercite_options[use_db]' /> Yes ";
  echo "<input type='radio' id='papercite_use_db' " . (!$option ? " checked='checked' " : "") . "value='no' name='papercite_options[use_db]' /> No";
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
