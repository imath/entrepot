<?php
/**
 * Plugin Name: Galerie
 * Plugin URI: https://github.com/imath/galerie/
 * Description: Une galerie d'extensions WordPress publiques créées par des artistes codeurs.
 * Version: 1.0.0-beta1
 * Requires at least: 4.8
 * Tested up to: 4.8
 * License: GNU/GPL 2
 * Author: imath
 * Author URI: https://imathi.eu/
 * Text Domain: galerie
 * Domain Path: /languages/
 * Network: True
 * GitHub Plugin URI: https://github.com/imath/galerie/
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Galerie' ) ) :
/**
 * Main plugin's class
 *
 * @package Galerie
 *
 * @since 1.0.0
 */
final class Galerie {

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
		$this->version = '1.0.0-beta1';

		// Domain
		$this->domain = 'galerie';

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
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		spl_autoload_register( array( $this, 'autoload' ) );

		require( $this->inc_dir . 'functions.php' );

		if ( is_admin() ) {
			require( $this->inc_dir . 'admin.php' );
		}

		require( $this->inc_dir . 'hooks.php'     );
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

		if ( false === strpos( $name, $this->domain ) ) {
			return;
		}

		$folder = null;
		$parts = explode( '-', $name );

		if ( isset( $parts[2] ) ) {
			$folder = $parts[2];
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
function galerie() {
	return Galerie::start();
}
add_action( 'plugins_loaded', 'galerie', 1 );
