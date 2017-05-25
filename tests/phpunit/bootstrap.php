<?php

require_once getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/functions.php';

define( 'PR_TESTING_ASSETS', dirname( __FILE__ ) . '/assets' );

function _bootstrap_plugin_repositories() {
	// Load The plugin
	require dirname( __FILE__ ) . '/../../plugin-repositories.php';
}
tests_add_filter( 'muplugins_loaded', '_bootstrap_plugin_repositories' );

require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';
