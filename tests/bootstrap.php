<?php

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

/* Path to the WordPress codebase you'd like to test. Add a backslash in the end. */
$_wp_path = getenv('WP_TESTS_WP_DIR');
if ( !$_wp_path ) $_wp_path = '/tmp/wordpress';
define( 'ABSPATH', $_wp_path);

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../papercite.php';
}
define('WP_PLUGIN_DIR', dirname(dirname(dirname( __FILE__ ))));
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

