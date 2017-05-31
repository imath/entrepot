<?php
/**
 * Galerie hooks.
 *
 * @package Galerie\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( is_multisite() ) {
	add_action( 'network_admin_menu', 'galerie_admin_add_menu' );
} else {
	add_action( 'admin_menu', 'galerie_admin_add_menu' );
}

add_action( 'admin_head', 'galerie_admin_head' );
add_action( 'admin_init', 'galerie_admin_register_scripts' );

// Plugins Install Screen > Galerie Tab.
add_filter( 'install_plugins_tabs', 'galerie_admin_repositories_tab', 10, 1 );
add_filter( 'install_plugins_table_api_args_galerie_repositories', 'galerie_admin_repositories_tab_args', 10, 1 );
add_action( 'install_plugins_galerie_repositories', 'galerie_admin_repositories_print_templates' );
add_action( 'install_plugins_pre_plugin-information', 'galerie_admin_repository_information', 5 );

// Override the Plugins API for Repositories.
add_filter( 'plugins_api', 'galerie_repositories_api', 10, 3 );

// Filters for modal content.
add_filter( 'galerie_repository_modal_content', 'galerie_sanitize_repository_content', 9 );
add_filter( 'galerie_repository_modal_content', 'links_add_target' );
