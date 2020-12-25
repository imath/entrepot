<?php
/**
 * Entrepôt hooks.
 *
 * @package Entrepôt\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'entrepot_setup_cache_group' );

// Enqueue scripts when needed.
add_action( 'admin_enqueue_scripts', 'entrepot_admin_enqueue_scripts' );

// Ease repositories identification
add_filter( 'extra_plugin_headers', 'entrepot_plugin_extra_header',    10, 1 );
add_filter( 'extra_theme_headers', 'entrepot_theme_extra_header',     10, 1 );

// Manage repository Updates.
add_action( 'set_site_transient_update_plugins', 'entrepot_update_plugin_repositories', 10, 1 );
add_action( 'set_site_transient_update_themes', 'entrepot_update_theme_repositories',  10, 1 );

// Filters for modal content.
add_filter( 'entrepot_repository_modal_content', 'entrepot_sanitize_repository_content', 9 );
add_filter( 'entrepot_repository_modal_content', 'links_add_target' );

// Registers REST API routes.
add_action( 'rest_api_init', 'entrepot_rest_routes', 100 );

// Theme Customizer hooks.
add_action( 'customize_register', 'entrepot_customize_register' );
add_filter( 'customize_load_themes', 'entrepot_customize_load_themes', 10, 2 );

// Load translations.
add_action( 'plugins_loaded', 'entrepot_load_textdomain', 9 );
