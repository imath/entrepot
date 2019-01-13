<?php
/**
 * Plugin Name: Entrepôt
 * Plugin URI: https://github.com/imath/entrepot/
 * Description: Une liste d'extensions, de blocs et de thèmes gratuits hébergés sur GitHub.com.
 * Version: 1.5.0
 * Requires at least: 4.8
 * Tested up to: 5.0
 * License: GNU/GPL 2
 * Author: imath
 * Author URI: https://imathi.eu/
 * Text Domain: entrepot
 * Domain Path: /languages/
 * Network: True
 * GitHub Plugin URI: https://github.com/imath/entrepot/
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Entrepot' ) ) :
/**
 * Main plugin's class
 *
 * @package Entrepôt
 *
 * @since 1.0.0
 */
final class Entrepot {

	/**
	 * Plugin's main instance
	 *
	 * @var object
	 */
	protected static $instance;

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->globals();
		$this->inc();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Setups plugin's globals
	 *
	 * @since 1.0.0
	 */
	private function globals() {
		// Version
		$this->version = '1.5.0';

		// Domain
		$this->domain = 'entrepot';

		// Base name
		$this->file      = __FILE__;
		$this->basename  = plugin_basename( $this->file );

		// Path and URL
		$this->dir              = plugin_dir_path( $this->file );
		$this->url              = plugin_dir_url ( $this->file );
		$this->js_url           = trailingslashit( $this->url . 'js' );
		$this->assets_url       = trailingslashit( $this->url . 'assets' );
		$this->assets_dir       = trailingslashit( $this->dir . 'assets' );
		$this->inc_dir          = trailingslashit( $this->dir . 'inc' );
		$this->repositories_dir = trailingslashit( $this->dir . 'repositories' );

		// Plugins missing dependencies.
		$this->miss_deps = array();

		// Plugins upgrade tasks.
		$this->upgrades = array();

		// Block supports
		$this->block_supports = function_exists( 'render_block' );
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		spl_autoload_register( array( $this, 'autoload' ) );

		require $this->inc_dir . 'functions.php';
		require $this->inc_dir . 'customizer.php';

		if ( $this->block_supports ) {
			require $this->inc_dir . 'blocks.php';
		}

		if ( is_admin() ) {
			require $this->inc_dir . 'admin.php';
		}

		// Load deprecated functions.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			require $this->inc_dir . 'deprecated.php';
		}

		require $this->inc_dir . 'hooks.php';
	}

	/**
	 * Class Autoload function
	 *
	 * @since  1.0.0
	 *
	 * @param  string $class The class name.
	 */
	public function autoload( $class ) {
		$name = str_replace( '_', '-', strtolower( $class ) );

		if ( false === strpos( $name, $this->domain ) && 'Parsedown' !== $class ) {
			return;
		}

		$path = $this->inc_dir . "classes/class-{$name}.php";

		// Sanity check.
		if ( ! file_exists( $path ) ) {
			return;
		}

		require $path;
	}
}

endif;

/**
 * Boot the plugin.
 *
 * @since 1.0.0
 */
function entrepot() {
	return Entrepot::start();
}
add_action( 'plugins_loaded', 'entrepot', 1 );
