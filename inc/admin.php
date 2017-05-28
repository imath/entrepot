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

/**
 * WP Ajax is overused by plugins.. Let's be sure we are
 * alone to request there.
 *
 * @since 1.0.0
 *
 * @return string Json reply.
 */
function galerie_admin_prepare_repositories_json_reply() {
	if ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'update_plugins' ) ) {
		wp_send_json( __( 'Vous n\'êtes pas autorisé à réaliser cette action.', 'galerie' ), 403 );
	}

	$json            = file_get_contents( galerie_assets_url() . 'galerie.min.json' );
	$repositories    = json_decode( $json );
	$installed_repos = galerie_get_installed_repositories();
	$keyed_by_slug   = array();

	foreach ( $installed_repos as $i => $installed_repo ) {
		$keyed_by_slug[ galerie_get_repository_slug( $i ) ] = $installed_repo;
	}

	if ( ! function_exists( 'install_plugin_install_status' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	}

	$thickbox_link = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=%s&amp;TB_iframe=true&amp;width=600&amp;height=550' );

	foreach ( $repositories as $k => $repository ) {
		$data = null;

		if ( ! isset( $repository->slug ) ) {
			$repositories[ $k ]->slug = strtolower( $repository->name );
		}

		$repositories[ $k ]->name = ucfirst( galerie_sanitize_repository_text( $repositories[ $k ]->name ) );

		if ( ! isset( $keyed_by_slug[ $repository->slug ] ) ) {
			unset( $repositories[ $k ] );
			continue;
		}

		$repositories[ $k ]->version    = $keyed_by_slug[ $repository->slug ]['Version'];
		$repositories[ $k ]->author_url = 'https://github.com/' . $repository->author;

		if ( ! empty( $keyed_by_slug[ $repository->slug ]['AuthorURI'] ) ) {
			$repositories[ $k ]->author_url = esc_url_raw( $keyed_by_slug[ $repository->slug ]['AuthorURI'] );
		}

		if ( ! empty( $keyed_by_slug[ $repository->slug ]['Name'] ) ) {
			$repositories[ $k ]->name = galerie_sanitize_repository_text( $keyed_by_slug[ $repository->slug ]['Name'] );
		}

		$repositories[ $k ]->description = (object) array_map( 'galerie_sanitize_repository_text', (array) $repositories[ $k ]->description );

		$data = install_plugin_install_status( $repository );
		foreach ( $data as $kd => $kv ) {
			$repositories[ $k ]->{$kd} = $kv;
		}

		$repositories[ $k ]->more_info = sprintf( __( 'Plus d\'informations sur %s' ), $repositories[ $k ]->name );
		$repositories[ $k ]->info_url  = sprintf( $thickbox_link, $repositories[ $k ]->slug );

		if ( in_array( $data['status'], array(), true ) ) {
			if ( is_plugin_active( $data['file'] ) ) {
				$repositories[ $k ]->active = true;
			} elseif ( current_user_can( 'activate_plugins' ) ) {
				$repositories[ $k ]->activate_url = add_query_arg( array(
					'_wpnonce'    => wp_create_nonce( 'activate-plugin_' . $status['file'] ),
					'action'      => 'activate',
					'plugin'      => $status['file'],
				), network_admin_url( 'plugins.php' ) );

				if ( is_network_admin() ) {
					$repositories[ $k ]->activate_url = add_query_arg( array( 'networkwide' => 1 ), $repositories[ $k ]->activate_url );
				}

				$repositories[ $k ]->activate_url = esc_url_raw( $repositories[ $k ]->activate_url );
			}
		}
	}

	wp_send_json( $repositories, 200 );
}

function galerie_admin_add_menu() {
	$screen = add_plugins_page(
		__( 'Repositories', 'galerie' ),
		__( 'Repositories', 'galerie' ),
		'manage_options',
		'repositories',
		'galerie_admin_menu'
	);

	add_action( "load-$screen", 'galerie_admin_prepare_repositories_json_reply' );
}

function galerie_admin_menu() {}

function galerie_admin_head() {
	remove_submenu_page( 'plugins.php', 'repositories' );
}

function galerie_admin_register_scripts() {
	wp_register_script(
		'galerie',
		sprintf( '%1$sgalerie%2$s.js', galerie_js_url(), galerie_min_suffix() ),
		array( 'wp-backbone' ),
		galerie_version(),
		true
	);

	wp_localize_script( 'galerie', 'galeriel10n', array(
		'url'          => esc_url_raw( add_query_arg( 'page', 'repositories', self_admin_url( 'plugins.php' ) ) ),
		'locale'       => get_user_locale(),
		'defaultIcon'  => esc_url_raw( galerie_assets_url() . 'repo.svg' ),
		'activePlugin' => _x( 'Actif', 'plugin', 'galerie' ),
		'installNow'   => _x( 'Installer', 'plugin', 'galerie' ),
		'updateNow'    => _x( 'Mettre à jour', 'plugin', 'galerie' ),
		'byAuthor'     => _x( 'De %s', 'plugin', 'galerie' ),
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
					<a href="{{{data.info_url}}}" class="thickbox open-plugin-details-modal">
					{{data.name}}
					<img src="{{{data.icon}}}" class="plugin-icon" alt="">
					</a>
				</h3>
			</div>
			<div class="action-links"></div>
			<div class="desc column-description">
				<p>{{data.presentation}}</p>
				<p class="authors">
					<cite>{{{data.author}}}</cite>
				</p>
			</div>
		</div>
	</script>
	<?php
}
