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

function plugin_repositories_get_plugin_latest_stable_release( $atom_url = '', $plugin = array() ) {
	if ( ! $atom_url  ) {
		// For Unit Testing purpose only. Do not use this constant in your code.
		if ( defined( 'PR_TESTING_ASSETS' ) && isset( $plugin['slug'] ) &&  'plugin-repositories' === $plugin['slug'] ) {
			$atom_url = trailingslashit( plugin_repositories()->dir ) . 'tests/phpunit/assets/releases';
		} else {
			return false;
		}
	}

	$atom_url = rtrim( $atom_url, '.atom' ) . '.atom';

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
	$tag_data->is_update = false;

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

		if ( isset( $plugin['Version'] ) && version_compare( $tag, $plugin['Version'], '<' ) ) {
			continue;
		}

		$response = array(
			'id'          => $release->id,
			'slug'        => '',
			'plugin'      => '',
			'new_version' => $tag,
			'url'         => '',
			'package'     => '',
		);

		if ( ! empty( $plugin ) ) {
			$response = wp_parse_args( array(
				'id'          => rtrim( str_replace( array( 'https://', 'http://' ), '', $plugin['GitHub Plugin URI'] ) ),
				'slug'        => $plugin['slug'],
				'plugin'      => $plugin['plugin'],
				'url'         => $plugin['GitHub Plugin URI'],
				'package'     => sprintf( '%1$sreleases/download/tag/%2$s/%3$s',
					trailingslashit( $plugin['GitHub Plugin URI'] ),
					$tag,
					sanitize_file_name( $plugin['slug'] . '.zip' )
				),
			), $response );
		}

		$tag_data = (object) $response;
		$tag_data->is_update = true;

		break;
	}

	return $tag_data;
}

function plugin_repositories_extra_header( $headers = array() ) {
	if (  ! isset( $headers['GitHub Plugin URI'] ) ) {
		$headers['GitHub Plugin URI'] = 'GitHub Plugin URI';
	}

	return $headers;
}
add_filter( 'extra_plugin_headers', 'plugin_repositories_extra_header', 10, 1 );

function plugin_repositories_update_plugin_repositories( $option = null ) {
	if ( ! did_action( 'http_api_debug' ) ) {
		return $option;
	}

	$plugins      = get_plugins();
	$repositories = array_diff_key( $plugins, wp_list_filter( $plugins, array( 'GitHub Plugin URI' => '' ) ) );

	$repositories_data = array();
	foreach ( $repositories as $kr => $dp ) {
		$repository_name = trim( dirname( $kr ), '/' );
		$json = plugin_repositories_get_repository_json( $repository_name );

		if ( ! $json || ! isset( $json->releases ) ) {
			continue;
		}

		$response = plugin_repositories_get_plugin_latest_stable_release( $json->releases, array_merge( $dp, array(
			'plugin' => $kr,
			'slug'   => $repository_name,
		) ) );

		$repositories_data[ $kr ] = $response;
	}

	$updated_repositories = wp_list_filter( $repositories_data, array( 'is_update' => true ) );

	if ( ! $updated_repositories ) {
		return $option;
	}

	if ( isset( $option->response ) ) {
		$option->response = array_merge( $option->response, $updated_repositories );
	} else {
		$option->response = $repositories_data;
	}

	// Prevent infinite loops.
	remove_filter( 'set_site_transient_update_plugins', 'plugin_repositories_update_plugin_repositories' );

	set_site_transient( 'update_plugins', $option );
	return $option;
}
add_filter( 'set_site_transient_update_plugins', 'plugin_repositories_update_plugin_repositories' );

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
