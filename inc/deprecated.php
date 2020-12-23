<?php
/**
 * Entrepôt deprecated functions.
 *
 * @package Entrepôt\inc
 *
 * @since 1.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gets the list of available repositories.
 *
 * @since 1.0.0
 * @deprecated 1.4.0
 *
 * @return array The list of available repositories.
 */
function entrepot_admin_get_repositories_list() {
	_deprecated_function( __FUNCTION__, '1.4.0', 'entrepot_admin_get_plugin_repositories_list()' );
	return entrepot_admin_get_plugin_repositories_list();
}

/**
 * Prints the the JavaScript templates.
 *
 * @since 1.0.0
 * @deprecated 1.4.0
 *
 * @return string HTML Output.
 */
function entrepot_admin_repositories_print_templates() {
	_deprecated_function( __FUNCTION__, '1.4.0', 'entrepot_admin_plugins_print_templates()' );
	return entrepot_admin_plugins_print_templates();
}

/**
 * Displays the repository's modal content.
 *
 * @since 1.0.0
 * @since 1.2.0 Use a split button to list all Repository links.
 * @deprecated 1.4.0
 *
 * @return string The repository's modal content.
 */
function entrepot_admin_repository_information() {
	_deprecated_function( __FUNCTION__, '1.4.0', 'entrepot_admin_plugin_details()' );
	return entrepot_admin_plugin_details();
}

/**
 * Gets the Repositories' dir.
 *
 * @since 1.0.0
 * @deprecated 1.4.0
 *
 * @return string Path to the repositories dir.
 */
function entrepot_plugins_dir() {
	_deprecated_function( __FUNCTION__, '1.4.0', 'entrepot_repositories_dir()' );
	return entrepot_repositories_dir();
}

/**
 * Checks with the GitHub releases of the Repository if there a new stable version available.
 *
 * @since 1.0.0
 * @deprecated 1.4.0
 *
 * @param  string $atom_url The Repository's feed URL.
 * @param  array  $plugin   The plugin's data.
 * @return object           The stable release data.
 */
function entrepot_get_plugin_latest_stable_release( $atom_url = '', $plugin = array() ) {
	_deprecated_function( __FUNCTION__, '1.4.0', 'entrepot_get_repository_latest_stable_release()' );
	return entrepot_get_repository_latest_stable_release( $atom_url, $plugin, 'plugin' );
}

/**
 * Adds a new Plugin's header tag to ease repositories identification
 * within the regular plugins.
 *
 * @since 1.0.0
 * @since 1.2.0 Add a new Plugin Header Tag to inform the plugin can be edited.
 * @deprecated 1.4.0
 *
 * @param  array  $headers  The current Plugin's header tag.
 * @return array            The repositories header tag.
 */
function entrepot_extra_header( $headers = array() ) {
	_deprecated_function( __FUNCTION__, '1.4.0', 'entrepot_plugin_extra_header()' );
	return entrepot_plugin_extra_header( $headers );
}

/**
 * Temporary private function to fix WordPress Plugin Updates count
 * on the Plugin Install screen.
 *
 * @see  https://core.trac.wordpress.org/ticket/41407
 * @deprecated 1.4.0 (Fixed in WordPress 4.9).
 *
 * @since  1.1.0
 */
function _entrepot_admin_fix_plugin_updates_count() {
	_deprecated_function( __FUNCTION__, '1.4.0' );
}


/**
 * Enqueues the needed JavaScript for the Manage Plugins versions Admin screen.
 *
 * @deprecated 1.6.0 (Fixed in WordPress 5.6).
 * @since 1.2.0
 */
function entrepot_admin_versions_load() {
	_deprecated_function( __FUNCTION__, '1.6.0' );
}

/**
 * Enqueues the needed script and style for the Plugin Versions screen.
 *
 * @deprecated 1.6.0 (Fixed in WordPress 5.6).
 * @since  1.4.0
 */
function entrepot_admin_versions_enqueue_scripts() {
	_deprecated_function( __FUNCTION__, '1.6.0' );
}

/**
 * Displays the Manage Plugins versions Admin screen.
 *
 * @deprecated 1.6.0 (Fixed in WordPress 5.6).
 * @since 1.2.0
 */
function entrepot_admin_versions() {
	_deprecated_function( __FUNCTION__, '1.6.0' );
}
