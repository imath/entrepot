<?php

require_once getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/functions.php';

define( 'PR_TESTING_ASSETS', dirname( __FILE__ ) . '/assets' );
define( 'ENTREPOT_ATOM_USE_FOPEN', true );

function _bootstrap_entrepot() {
	// Load The plugin
	require dirname( __FILE__ ) . '/../../entrepot.php';

	if ( ! is_dir( getenv( 'WP_DEVELOP_DIR' ) . '/src/wp-content/plugins/entrepot' ) ) {
		@symlink(  dirname( dirname( dirname( __FILE__ ) ) ), getenv( 'WP_DEVELOP_DIR' ) . '/src/wp-content/plugins/entrepot' );
	}
}
tests_add_filter( 'muplugins_loaded', '_bootstrap_entrepot' );

require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';
