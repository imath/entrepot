<?php
/**
 * Plugin Repositories function.
 *
 * @package PluginRepositories\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function plugin_repositories_assets_url() {
	return plugin_repositories()->assets_url;
}

function plugin_repositories_plugins_dir() {
	return apply_filters( 'plugin_repositories_plugins_dir', plugin_repositories()->repositories_dir );
}

function plugin_repositories_get_repository_json( $plugin = '' ) {
	if ( ! $plugin ) {
		return false;
	}

	$json = sprintf( '%1$s/%2$s.json', plugin_repositories_plugins_dir(), sanitize_file_name( $plugin ) );
	if ( ! file_exists( $json ) ) {
		return false;
	}

	$data = file_get_contents( $json );
	return json_decode( $data );
}

function plugin_repositories_get_plugin_latest_stable_release( $atom_url = '' ) {
	if ( ! $atom_url ) {
		return false;
	}

	if ( ! class_exists( 'AtomParser') ) {
		require_once( ABSPATH . WPINC . '/atomlib.php' );
	}

	$atom = new AtomParser();
	$atom->FILE = $atom_url;
	$atom->parse();

	if ( ! isset( $atom->feed ) || ! isset( $atom->feed->entries ) ) {
		return false;
	}

	$tag_data = new stdClass;

	foreach ( $atom->feed->entries as $release ) {
		if ( ! isset( $release->id ) ) {
			continue;
		}

		$id     = explode( '/', $release->id );
		$tag    = $id[ count( $id ) - 1 ];
		$stable = str_replace( '.', '', $tag );

		if ( ! is_numeric( $stable ) ) {
			continue;
		}

		$tag_data->tag  = $tag;
		$tag_data->date = $release->updated;
		$tag_data->note = end( $release->content );

		break;
	}

	return $tag_data;
}

function plugin_repositories_admin_home() {
	?>
	<h1><?php esc_html_e( 'Repositories', 'plugin-repositories' ); ?></h1>

	<div class="wrap">
		<?php var_dump( get_site_transient( 'update_plugins' ) ); ?>
	</div>
	<?php
}

function plugin_repositories_add_menu() {
	add_menu_page(
		__( 'Repositories', 'plugin-repositories' ),
		__( 'Repositories', 'plugin-repositories' ),
		'manage_options',
		'repositories',
		'plugin_repositories_admin_home',
		plugin_repositories_assets_url() . 'repo.svg'
	);
}
