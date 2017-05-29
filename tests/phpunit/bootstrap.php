<?php

require_once getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/functions.php';

define( 'PR_TESTING_ASSETS', dirname( __FILE__ ) . '/assets' );
define( 'GALERIE_ATOM_USE_FOPEN', true );

function _bootstrap_galerie() {
	// Load The plugin
	require dirname( __FILE__ ) . '/../../galerie.php';

	if ( ! is_dir( getenv( 'WP_DEVELOP_DIR' ) . '/src/wp-content/plugins/galerie' ) ) {
		@symlink(  dirname( dirname( dirname( __FILE__ ) ) ), getenv( 'WP_DEVELOP_DIR' ) . '/src/wp-content/plugins/galerie' );
	}
}
tests_add_filter( 'muplugins_loaded', '_bootstrap_galerie' );

require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';
