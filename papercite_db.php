<?php

/**
* Database management
* See http://codex.wordpress.org/Creating_Tables_with_Plugins
*/

global $wpdb;
global $papercite_db_version;
global $papercite_table_name;
$papercite_table_name = $GLOBALS["wpdb"]->prefix . "plugin_papercite";
$papercite_db_version = "1.3";


function papercite_msg_upgraded() {
    print "<p style='text-align: center; color: blue'>Papercite plugin: database updated to version $GLOBALS[papercite_db_version]</p>";
}

function papercite_install() {
    global $wpdb, $papercite_db_version, $papercite_table_name;

     $installed_ver = get_option( "papercite_db_version" );
     if ($installed_ver != $papercite_db_version) {
         $sql = "CREATE TABLE $papercite_table_name (
             url VARCHAR(300) CHARSET ASCII NOT NULL,  
             bibtexid VARCHAR(200) CHARSET ASCII NOT NULL,
             entrytype VARCHAR(80) CHARSET ASCII NOT NULL,
             year SMALLINT,
             data TEXT NOT NULL,
             PRIMARY KEY id (url, bibtexid),
             INDEX year (year),
             INDEX entrytype (entrytype)
          ) DEFAULT CHARACTER SET $wpdb->charset";
          
        // Install / upgrade
         require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
         dbDelta($sql, true);
         if ($wpdb->last_result !== FALSE) {
             // Set the current version
             update_option("papercite_db_version", $papercite_db_version);
             add_action('admin_notices', 'papercite_msg_upgraded');
         }
         
     }
}

papercite_install();


?>