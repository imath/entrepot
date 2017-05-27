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

add_action( 'admin_menu', 'galerie_add_menu' );
