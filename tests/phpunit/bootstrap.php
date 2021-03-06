<?php
// If we're running in WP's build directory, ensure that WP knows that, too.
if ( 'build' === getenv( 'LOCAL_DIR' ) ) {
	define( 'WP_RUN_CORE_TESTS', true );
}

// Should we use wp-phpunit?
if ( getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	require_once dirname( __FILE__, 3 ) . '/vendor/autoload.php';

	if ( getenv( 'WP_PHPUNIT__DIR' ) ) {
		define( 'WP_TESTS_DIR', getenv( 'WP_PHPUNIT__DIR' ) );
	}
}

// Defines WP_TEST_DIR & WP_DEVELOP_DIR if not already defined.
if ( ! defined( 'WP_TESTS_DIR' ) ) {
	$wp_develop_dir = getenv( 'WP_DEVELOP_DIR' );
	if ( ! $wp_develop_dir ) {
		if ( defined( 'WP_DEVELOP_DIR' ) ) {
			$wp_develop_dir = WP_DEVELOP_DIR;
		} else {
			$wp_develop_dir = dirname( __FILE__, 7 );
		}
	}

	define( 'WP_DEVELOP_DIR', $wp_develop_dir );
	define( 'WP_TESTS_DIR', $wp_develop_dir . '/tests/phpunit' );
}

if ( ! file_exists( WP_TESTS_DIR . '/includes/functions.php' ) ) {
	die( "The WordPress PHPUnit test suite could not be found.\n" );
}

require_once WP_TESTS_DIR . '/includes/functions.php';

define( 'PR_TESTING_ASSETS', dirname( __FILE__ ) . '/assets' );
define( 'ENTREPOT_ATOM_USE_FOPEN', true );

// Bootsrap the plugin.
function _bootstrap_entrepot() {
	// Load The plugin.
	require dirname( __FILE__ ) . '/../../entrepot.php';

	if ( defined( 'WP_DEVELOP_DIR' ) && ! is_dir( WP_DEVELOP_DIR . '/src/wp-content/plugins/entrepot' ) ) {
		@symlink(  dirname( dirname( dirname( __FILE__ ) ) ), WP_DEVELOP_DIR . '/src/wp-content/plugins/entrepot' );
	}
}
tests_add_filter( 'muplugins_loaded', '_bootstrap_entrepot' );

require WP_TESTS_DIR . '/includes/bootstrap.php';
