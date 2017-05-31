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

		$repositories[ $k ]->name       = galerie_sanitize_repository_text( $repositories[ $k ]->name );
		$repositories[ $k ]->author_url = 'https://github.com/' . $repository->author;
		$repositories[ $k ]->id         = $repository->author . '_' . $repository->slug;

		// Always install the latest version.
		if ( ! isset( $keyed_by_slug[ $repository->slug ] ) ) {
			$repositories[ $k ]->version = 'latest';

		// Inform about the installed version.
		} else {
			$repositories[ $k ]->version = $keyed_by_slug[ $repository->slug ]['Version'];

			if ( ! empty( $keyed_by_slug[ $repository->slug ]['AuthorURI'] ) ) {
				$repositories[ $k ]->author_url = esc_url_raw( $keyed_by_slug[ $repository->slug ]['AuthorURI'] );
			}

			if ( ! empty( $keyed_by_slug[ $repository->slug ]['Name'] ) ) {
				$repositories[ $k ]->name = galerie_sanitize_repository_text( $keyed_by_slug[ $repository->slug ]['Name'] );
			}
		}

		$repositories[ $k ]->description = (object) array_map( 'galerie_sanitize_repository_text', (array) $repositories[ $k ]->description );

		$data = install_plugin_install_status( $repository );
		foreach ( $data as $kd => $kv ) {
			$repositories[ $k ]->{$kd} = $kv;
		}

		$repositories[ $k ]->more_info = sprintf( __( 'Plus d\'informations sur %s', 'galerie' ), $repositories[ $k ]->name );
		$repositories[ $k ]->info_url  = sprintf( $thickbox_link, $repositories[ $k ]->slug );

		if ( in_array( $data['status'], array( 'latest_installed', 'newer_installed' ), true ) ) {
			if ( is_plugin_active( $data['file'] ) ) {
				$repositories[ $k ]->active = true;
			} elseif ( current_user_can( 'activate_plugins' ) ) {
				$repositories[ $k ]->activate_url = add_query_arg( array(
					'_wpnonce'    => wp_create_nonce( 'activate-plugin_' . $data['file'] ),
					'action'      => 'activate',
					'plugin'      => $data['file'],
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

/**
 * Register the Repositories Administration page
 *
 * @since 1.0.0
 */
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

/**
 * The Repositories Administration page
 *
 * @since 1.0.0
 */
function galerie_admin_menu() {}

/**
 * Removes the Repositories Administration page from the Admin Menu.
 *
 * @since 1.0.0
 */
function galerie_admin_head() {
	remove_submenu_page( 'plugins.php', 'repositories' );
}

/**
 * Register Administration scripts.
 *
 * @since 1.0.0
 */
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
		'byAuthor'     => _x( 'De %s', 'plugin', 'galerie' ),
	) );
}

/**
 * Adds a new tab to the Plugins Install screen.
 *
 * @since 1.0.0
 *
 * @param  array  $tabs The list of tabs.
 * @return array        The list of tabs.
 */
function galerie_admin_repositories_tab( $tabs = array() ) {
	return array_merge( $tabs, array( 'galerie_repositories' => __( 'Galerie', 'galerie' ) ) );
}

/**
 * Sets specific query args for the Galerie's Tab.
 *
 * @since 1.0.0
 *
 * @param  array  $args Query arguments.
 * @return array        Query arguments.
 */
function galerie_admin_repositories_tab_args( $args = false ) {
	return array( 'galerie' => true, 'per_page' => 0 );
}

/**
 * Shortcircuits the Plugins API for repositories
 *
 * @since 1.0.0
 *
 * @param  boolean $res    False Not Shortcircuit.
 * @param  string  $action The Query type.
 * @param  object  $args   Query arguments.
 * @return object|boolean  The Plugins API response.
 *                         False when not Shortcircuited.
 */
function galerie_repositories_api( $res = false, $action = '', $args = null ) {
	if ( 'query_plugins' === $action && ! empty( $args->galerie ) ) {
		wp_enqueue_script( 'galerie' );
		$res = (object) array(
			'plugins' => array(),
			'info'    => array( 'results' => 0 ),
		);
	} elseif ( 'plugin_information' === $action && ! empty( $args->slug ) ) {
		$json = galerie_get_repository_json( $args->slug );

		if ( $json && $json->releases ) {
			$res = galerie_get_plugin_latest_stable_release( $json->releases, array(
				'plugin'            => $json->name,
				'slug'              => $args->slug,
				'Version'           => 'latest',
				'GitHub Plugin URI' => str_replace( '/releases', '', $json->releases ),
			) );
		}
	}

	return $res;
}

/**
 * Prints the the JavaScript templates.
 *
 * @since 1.0.0
 *
 * @return string HTML Output.
 */
function galerie_admin_repositories_print_templates() {
	?>
	<div class="wp-list-table widefat plugin-install">
		<h2 class="screen-reader-text"><?php esc_html_e( 'Liste des dépôts', 'galerie' ); ?></h2>
		<div id="the-list" data-list="galerie"></div>
	</div>
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
			<div class="action-links">
				<ul class="plugin-action-buttons">
				<# if ( data.status ) { #>
					<li>
						<# if ( 'install' === data.status && data.url ) { #>
							<a class="install-now button" data-slug="{{data.slug}}" href="{{{data.url}}}" aria-label="<?php esc_attr_e( 'Installer maintenant', 'galerie' ); ?>" data-name="{{data.name}}"><?php esc_html_e( 'Installer', 'galerie' ); ?></a>

						<# } else if ( 'update_available' === data.status && data.url ) { #>
							<a class="update-now button aria-button-if-js" data-plugin="{{data.file}}" data-slug="{{data.slug}}" href="{{{data.url}}}" aria-label="<?php esc_attr_e( 'Mettre à jour maintenant', 'galerie' ); ?>" data-name="{{data.name}}"><?php esc_html_e( 'Mettre à jour', 'galerie' ); ?></a>

						<# } else if ( data.activate_url ) { #>
							<a href="{{{data.activate_url}}}" class="button activate-now" aria-label="<?php echo is_network_admin() ? esc_attr__( 'Activer sur le réseau', 'galerie' ) : esc_attr__( 'Activer', 'galerie' ); ?>"><?php echo is_network_admin() ? esc_html__( 'Activer sur le réseau', 'galerie' ) : esc_html__( 'Activer', 'galerie' ); ?></a>

						<# } else if ( data.active ) { #>
							<button type="button" class="button button-disabled" disabled="disabled"><?php esc_html_e( 'Actif', 'galerie' ); ?></button>

						<# } else { #>
							<button type="button" class="button button-disabled" disabled="disabled"><?php esc_html_e( 'Installé', 'galerie' ); ?></button>

						<# } #>
					</li>
				<# } #>
					<li>
						<a href="{{{data.info_url}}}" class="thickbox open-plugin-details-modal" aria-label="{{data.more_info}}" data-title="{{data.name}}"><?php esc_html_e( 'Plus de détails', 'galerie' ); ?></a>
					</li>
				</ul>
			</div>
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

/**
 * Displays the repository's modal content.
 *
 * @since 1.0.0
 *
 * @return string The repository's modal content.
 */
function galerie_admin_repository_information() {
	global $tab;

	if ( empty( $_REQUEST['plugin'] ) ) {
		return;
	}

	$plugin = wp_unslash( $_REQUEST['plugin'] );
	$output = array(
		'title'   => __( 'Détails du dépôt', 'galerie' ),
		'text'    => '',
		'type'    => 'error',
		'context' => 'repository_information',
	);

	if ( isset( $_REQUEST['section'] ) && 'changelog' === $_REQUEST['section'] ) {
		$repository_updates = get_site_transient( 'update_plugins' );

		if ( empty( $repository_updates->response ) ) {
			return;
		}

		$repository = wp_list_filter( $repository_updates->response, array( 'slug' => $plugin ) );
		if ( empty( $repository ) || 1 !== count( $repository ) ) {
			return;
		}

		$repository        = reset( $repository );
		$output['title']   = __( 'Détails de la mise à jour', 'galerie' );
		$output['context'] = 'repository_upgrade_notice';

		if ( ! empty( $repository->full_upgrade_notice ) ) {
			$upgrade_info = html_entity_decode( $repository->full_upgrade_notice, ENT_QUOTES, get_bloginfo( 'charset' ) );
			$output['text'] = $upgrade_info;
			$output['type'] = 'success';

		} else {
			$output['text'] = __( 'Désolé ce dépôt n\'a pas inclu d\'informations de mise à jour pour cette version.', 'galerie' );
		}
	} else {
		$repository_data = galerie_get_repository_json( $plugin );

		if ( ! $repository_data ) {
			return;
		}

		$output['text'] = __( 'Désolé, les détails concernant ce dépôt ne sont pas disponibles pour le moment.', 'galerie' );
		$has_readme     = false;

		if ( ! empty( $repository_data->README ) ) {
			$request  = wp_remote_get( $repository_data->README, array(
				'timeout'    => 30,
				'user-agent' => 'Galerie/WordPress-Plugin-Updater; ' . get_bloginfo( 'url' ),
			) );

			if ( ! is_wp_error( $request ) && 200 === (int) wp_remote_retrieve_response_code( $request ) ) {
				$repository_info = wp_remote_retrieve_body( $request );
				$parsedown       = new Parsedown();
				$output['text']  = $parsedown->text( $repository_info );
				$output['type']  = 'success';
			}
		}
	}

	wp_enqueue_style( 'galerie',
		sprintf( '%1$sstyle%2$s.css', galerie_assets_url(), galerie_min_suffix() ),
		array( 'common' ),
		galerie_version()
	);
	iframe_header( strip_tags( $output['title'] ) ); ?>

	<div id="plugin-information-scrollable" class="galerie">
		<div id="section-holder" class="wrap">

		<?php if ( 'success' === $output['type'] ) :
			/**
			 * Use this filter to add extra formatting.
			 *
			 * @since 1.0.0
			 *
			 * @param string $text   The Content to output. (Repo Information or Upgrade notice).
			 * @param array  $output {
			 *  An array of arguments.
			 *
			 *  @type string $title   The page title.
			 *  @type string $text    The content to output.
			 *  @type string $type    Whether it's as a success or an error.
			 *  @type string $context Whether it's the repo description or the upgrade notice.
			 * }
			 */
			echo apply_filters( 'galerie_repository_modal_content', $output['text'], $output );
		else :
			printf( '<div id="message" class="error"><p>%s</p></div>', esc_html( $output['text'] ) );
		endif ; ?>

		</div>
	</div>

	<?php if ( ! empty( $repository_data->issues ) ) :
		$base_url = str_replace( 'issues', '', rtrim( $repository_data->issues, '/' ) );
	?>
		<div id='<?php echo esc_attr( $tab ); ?>-footer'>
			<a class="button button-primary right" href="<?php echo esc_url( $repository_data->issues ); ?>" target="_blank"><?php esc_html_e( 'Rapporter une anomalie', 'galerie' ); ?></a>
			<a class="button button-secondary" href="<?php echo esc_url( $base_url ); ?>" target="_blank"><?php esc_html_e( 'Voir sur Github', 'galerie' ); ?></a>
			<a class="button button-secondary" href="<?php echo esc_url( $base_url . 'pulls' ); ?>" target="_blank"><?php esc_html_e( 'Contribuer', 'galerie' ); ?></a>
		</div>
	<?php endif ; ?>

	<?php iframe_footer();
	exit;
}
