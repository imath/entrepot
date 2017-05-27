<?php
/**
 * Galerie Admin functions.
 *
 * @package Galerie\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function galerie_admin_register_scripts() {
	wp_register_script(
		'galerie',
		sprintf( '%1$sgalerie%2$s.js', galerie_js_url(), galerie_min_suffix() ),
		array( 'jquery' ),
		galerie_version(),
		true
	);

	wp_localize_script( 'galerie', 'Galerie', array(
		'url' => galerie_assets_url() . 'galerie.min.json',
	) );
}

function galerie_admin_repositories_tab( $tabs = array() ) {
	return array_merge( $tabs, array( 'galerie_repositories' => __( 'Galerie', 'galerie' ) ) );
}

function galerie_admin_repositories_tab_args( $args = false ) {
	return array( 'galerie' => true, 'per_page' => 0 );
}

function galerie_repositories_api( $res = false, $action = '', $args = null ) {
	if ( 'query_plugins' === $action && ! empty( $args->galerie ) ) {
		wp_enqueue_script( 'galerie' );
		$res = (object) array(
			'plugins' => array(),
			'info'    => array( 'results' => 0 ),
		);
	}

	return $res;
}
