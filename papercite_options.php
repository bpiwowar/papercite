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

function papercite_checked_files_cell($key, $folder, $suffix, $ext, $mime)
{
    return "<tr>"
    . "<td><input name='papercite_options[checked_files_key][] type='text' value='".htmlspecialchars($key)."'/></td>"
    . "<td><input name='papercite_options[checked_files_folder][] type='text' value='".htmlspecialchars($folder)."'/></td>"
    . "<td><input name='papercite_options[checked_files_suffix][] type='text'  value='".htmlspecialchars($suffix)."'/></td>"
    . "<td><input name='papercite_options[checked_files_ext][] type='text'  value='".htmlspecialchars($ext)."'/></td>"
    . "<td><input name='papercite_options[checked_files_mime][] type='text'  value='".htmlspecialchars($mime)."'/></td>"
    . "<td><span class='papercite_checked_files'>-</span><span class='papercite_checked_files'>+</span></td></tr>";
}

function papercite_options_page() {
  wp_enqueue_script( 'json2' );
  wp_enqueue_script( 'jquery-ui-dialog' );
?>
  <div>
    <h2>Papercite options</h2>
    
    Options related to the papercite plugin.
    
    <form action="options.php" method="post">

    <?php settings_fields('papercite_options'); ?>
    <?php do_settings_sections('papercite'); ?>
    <input type='hidden' name='papercite_options[form]' value='1'>
    
    <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
    </form>
    </div>

  <script type="text/javascript" >
  jQuery("#papercite_create_db").click(function() {
    var data = {
      action: 'papercite_create_db'
    };

    jQuery.post(ajaxurl, data, function(response) {
        var r = JSON.parse(response);
        var d = jQuery("<div style='background:white; border: 1px solid black; padding: 3px; margin: 3px; '></div>");
        if (r[0] == 0) {
          d.html("Table created").dialog({modal: true});
          jQuery("#papercite_db_nok").hide();
          jQuery("#papercite_db_ok").show();
        } else d.html(r[1]).dialog({modal: true});
    });
  });

  jQuery("#papercite_clear_db").click(function() {
    var data = {
      action: 'papercite_clear_db'
    };

    jQuery.post(ajaxurl, data, function(response) {
        var r = JSON.parse(response);
        var d = jQuery("<div style='background:white; border: 1px solid black; padding: 3px; margin: 3px; '></div>");
        if (r[1] == "") {
          d.html("Cache cleared").dialog({modal: true});
        } else d.html("Error: " + r[1]).dialog({modal: true});
    });
  });

  jQuery(document).on("click", "span.papercite_checked_files", function() {
    var cell = jQuery(this).parents().eq(1);
    if (this.textContent == "+") 
    {
      cell.parent().append(<?php print json_encode(papercite_checked_files_cell("","","","","")); ?>);
    }
    else if (this.textContent == "-")
    {
      cell.remove();
    }
  });
  
  </script>
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
  add_settings_field('auto_bibshow', 'Auto bibshow', 'papercite_auto_bibshow', 'papercite', 'papercite_choices');
  add_settings_field('skip_for_post_lists', 'Skip for post lists', 'papercite_skip_for_post_lists', 'papercite', 'papercite_choices');
  add_settings_field('process_titles', 'Process titles', 'papercite_process_titles', 'papercite', 'papercite_choices');


  add_settings_section('papercite_files', 'Attached files', 'papercite_files_text', 'papercite');
  add_settings_field('use_media', 'Methods', 'papercite_files_checkers', 'papercite', 'papercite_files');
  add_settings_field('checked_files', 'Checked files', 'papercite_checked_files', 'papercite', 'papercite_files');
}

function papercite_section_text() {
  echo '<p>Set the default settings - leave the fields empty to use papercite default values</p>';
} 


function papercite_choices_text() {
  echo '<p>Options to set how papercite process the data</p>';
} 

function papercite_files_text() {
  echo '<p>How attached files are detected - and associated to (bibtex) fields</p>';
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


add_action('wp_ajax_papercite_create_db', 'papercite_ajax_create_db');
add_action('wp_ajax_papercite_clear_db', 'papercite_ajax_clear_db');

function papercite_ajax_clear_db() {
    global $wpdb;
    require_once(dirname(__FILE__) . "/papercite_db.php");
    // $wpdb->show_errors(true);

    ob_start();
    $result = $wpdb->query($wpdb->prepare("DELETE FROM $papercite_table_name_url"));
    
    if ($result !== FALSE) 
    {
        $result = $wpdb->query($wpdb->prepare("DELETE FROM $papercite_table_name"));        
        if ($result !== FALSE) 
        {
            $out = ob_get_flush();
            $result = TRUE;
        }
    }

    $out = ob_get_contents();
    ob_end_clean();
    print json_encode(Array($result ? 0 : 1, $out));
	die(); 
}

function papercite_ajax_create_db() {
    require_once(dirname(__FILE__) . "/papercite_db.php");
    print json_encode(papercite_install(true));
	die(); 
}

function papercite_use_db() {
  $option = $GLOBALS["papercite"]->options["use_db"];


  require_once(dirname(__FILE__) . "/papercite_db.php");
  global $papercite_table_name_url, $wpdb;

  $exists =  sizeof($wpdb->get_col("SHOW TABLES LIKE '$papercite_table_name'")) == 1;
 
  echo "<div>Papercite can use a database backend to avoid reparsing bibtex files and loading the full data each time<div>";
  print "<div id=\"papercite_db_ok\" style='" . ($exists ? "" : "display:none;"). "color:blue'>The database has been created.</div>";
  print "<div id=\"papercite_db_nok\" style='" .(!$exists ? "" : "display:none;"). ($option ? "color:red;" : ""). "'>The database does not exist. [<span class='papercite_link' id='papercite_create_db'>Create</span>]</div>";

  if ($exists) {
    // Display some information
    print "<div class='papercite_info'>" . $wpdb->get_var("SELECT count(*) FROM $papercite_table_name") . " entries in the database</div>";
    print "<div class='papercite_info'>Cached bibtex files: " . 
      implode(", ", $wpdb->get_col("SELECT url from $papercite_table_name_url")) . "</div>";
    print "<div style='margin: 10px 0 10px'><span class='papercite_link' id='papercite_clear_db'>Clear cache</a></div>";
  }

  echo "<input type='radio' id='papercite_use_db' " . ($option ? " checked='checked' " : "") . " value='yes' name='papercite_options[use_db]' /> Yes ";
  echo "<input type='radio' id='papercite_use_db' " . (!$option ? " checked='checked' " : "") . "value='no' name='papercite_options[use_db]' /> No";
  
}

function papercite_auto_bibshow() {
  $options = $GLOBALS["papercite"]->options;
  echo "<input id='papercite_auto_bibshow' name='papercite_options[auto_bibshow]' type='checkbox' value='1' " . checked(true, $options['auto_bibshow'], false) . " /> This will automatically insert [bibshow] (with default settings) when an unexpected [bibcite] is found.";
}

function papercite_files_checkers() {
  $options = $GLOBALS["papercite"]->options;
  echo "<div><input id='papercite_use_media' name='papercite_options[use_media]' type='checkbox' value='1' " . checked(true, $options['use_media'], false) . " /> Search files from WordPress media</div>";
  echo "<div><input id='papercite_use_files' name='papercite_options[use_files]' type='checkbox' value='1' " . checked(true, $options['use_files'], false) . " /> Search files in the papercite folders.</div>";
}

function papercite_skip_for_post_lists() {
  $options = $GLOBALS["papercite"]->options;
  echo "<input id='papercite_skip_for_post_lists' name='papercite_options[skip_for_post_lists]' type='checkbox' value='1' " . checked(true, $options['skip_for_post_lists'], false) . " /> This will skip papercite processing when displaying a list of posts or pages. [bibcite] and [bibshow] tags will be stripped.";
}

function papercite_process_titles() {
  $options = $GLOBALS["papercite"]->options;
  echo "<input id='papercite_process_titles' name='papercite_options[process_titles]' type='checkbox' value='1' " . checked(true, $options['process_titles'], false) . " /> This will process the title fields (title, booktitle) as BibTeX, that is, lowercasing everything which is not between braces.";  
} 

function papercite_checked_files() 
{
  $options = $GLOBALS["papercite"]->options["checked_files"];
  print <<<EOS
  <div>These settings determine <i>how</i> a file can be automatically matched given a bibtex entry. First, the key of the bibtex entry is transformed - lowercased, and the characters <code>:</code> and <code>/</code> are replaced by <code>-</code>. The <b>field</b> determines the bibtex field that will be populated when matching. Then, 
  <dl>
    <dt>Filesystem matching<dt><dd>A file will match if it is contained in the <b>folder</b> and its name is  <b>[key]</b><b>[suffix]</b>.<b>extension</b></dd>
    <dt>WordPress media matching<dt><dd>A file will match if its mime-type corresponds (or is empty) and its permalink name matches <b>[key]</b><b>[suffix]</b> </dd>
  </dl>
</div>
<table class='papercite_checked_files'><thead style='text-align: center'><th>Field</th><th>Folder</th><th>Suffix</th>
    <th>Extension</th><th>Mime-type</th><th><span class='papercite_checked_files'>+</span></th></thead>
EOS;
  foreach($options as $x) 
  {
    if (sizeof($x) == 3) {
      // convert to new format
      $x[3] = $x[2];
      $x[2] = "";
      $x[4] = "";
    }
    print papercite_checked_files_cell($x[0], $x[1], $x[2], $x[3], $x[4]);
  }
  print "</table>";
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
  $options['auto_bibshow'] = $input['auto_bibshow'] == "1";
  $options['use_media'] = $input['use_media'] == "1";
  $options['use_files'] = $input['use_files'] == "1";
  $options['skip_for_post_lists'] = $input['skip_for_post_lists'] == "1";
  $options['process_titles'] = $input['process_titles'] == "1";

  $options['file'] = trim($input['file']);
  $options['timeout'] = trim($input["timeout"]);

  if (array_key_exists('form', $input)) 
  {
    $a = Array();
    for($i = 0; $i < sizeof($input["checked_files_ext"]); $i++) 
    {
      $key = $input["checked_files_key"][$i];
      $folder = $input["checked_files_folder"][$i];
      $suffix = $input["checked_files_suffix"][$i];
      $ext = $input["checked_files_ext"][$i];
      $mime = $input["checked_files_mime"][$i];
      if (!empty($key) && !empty($folder) && !empty($ext))
      {
        $a[] = Array($key, $folder, $suffix, $ext, $mime);
      }
    }
    $options['checked_files'] = &$a;
  }
  
  papercite_set($options, $input, "bibshow_template");
  papercite_set($options, $input, "bibtex_template");
  papercite_set($options, $input, "format");
  papercite_set($options, $input, "bibtex_parser");
  
  return $options;
}

function papercite_bibtype2string($type) {
    switch($type) {
        case "article":     
            return __("Journal/magazine article", "papercite");

        case "conference":
        case "inproceedings":
            return __("Paper in conference proceedings", "papercite");
        
        case "manual": 
            return __("Technical documentation", "papercite");

        case "mastersthesis": 
            return __("Master's thesis", "papercite");

        case "phdthesis": 
            return __("Ph.D. thesis", "papercite");

        case "proceedings": 
            return __("Conference proceedings", "papercite");

        case "techreport": 
            return __("Technical report", "papercite");

        case "incollection": 
            return __("Book chapter", "papercite");
        
        default: return __(ucfirst($type), "papercite");
    }
}
?>
