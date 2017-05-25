<?php
/**
 * Plugin Repositories hooks.
 *
 * @package PluginRepositories\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'plugin_repositories_add_menu' );
