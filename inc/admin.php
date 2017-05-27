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
		array( 'wp-backbone' ),
		galerie_version(),
		true
	);

	wp_localize_script( 'galerie', 'galeriel10n', array(
		'url'         => galerie_assets_url() . 'galerie.min.json',
		'locale'      => get_user_locale(),
		'defaultIcon' => esc_url_raw( galerie_assets_url() . 'repo.svg' ),
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

function galerie_admin_repositories_print_templates() {
	?>
	<div id="the-list" data-list="galerie"></div>
	<script type="text/html" id="tmpl-galerie-repository">
		<div class="plugin-card-top">
			<div class="name column-name">
				<h3>
					<a href="#" class="thickbox open-plugin-details-modal">
					{{data.name}}
					<img src="{{{data.icon}}}" class="plugin-icon" alt="">
					</a>
				</h3>
			</div>
			<div class="action-links"></div>
			<div class="desc column-description">
				<p>{{data.presentation}}</p>
				<p class="authors">{{data.author}}</p>
			</div>
		</div>
	</script>
	<?php
}
