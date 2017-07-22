<?php
/**
 * Entrepôt Admin functions.
 *
 * @package Entrepôt\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Entrepôt's Upgrader.
 *
 * @since 1.0.0
 */
function entrepot_admin_updater() {
	if ( ! version_compare( entrepot_db_version(), entrepot_version(), '<' ) ) {
		return;
	}

	// New repositories can be added each time the Entrepôt has a new release.
	wp_cache_delete( 'repositories', 'entrepot' );

	if ( 1.1 === (float) entrepot_version() ) {
		set_site_transient( 'entrepot_notice_example', true, DAY_IN_SECONDS );
	}

	// Update Entrepôt version.
	update_network_option( 0, '_entrepot_version', entrepot_version() );
}

/**
 * Gets the list of available repositories.
 *
 * @since 1.0.0
 *
 * @return array The list of available repositories.
 */
function entrepot_admin_get_repositories_list() {
	$repositories    = entrepot_get_repositories();
	$installed_repos = entrepot_get_installed_repositories();
	$keyed_by_slug   = array();

	foreach ( $installed_repos as $i => $installed_repo ) {
		$keyed_by_slug[ entrepot_get_repository_slug( $i ) ] = $installed_repo;
	}

	if ( ! function_exists( 'install_plugin_install_status' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	}

	$thickbox_link = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=%s&amp;TB_iframe=true&amp;width=600&amp;height=550' );

	foreach ( $repositories as $k => $repository ) {
		$data = null;

		if ( ! isset( $repository->slug ) ) {
			$repositories[ $k ]->slug = sanitize_title( $repository->name );
		}

		$repositories[ $k ]->name       = entrepot_sanitize_repository_text( $repositories[ $k ]->name );
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
				$repositories[ $k ]->name = entrepot_sanitize_repository_text( $keyed_by_slug[ $repository->slug ]['Name'] );
			}
		}

		if ( isset( $repositories[ $k ]->dependencies ) ) {
			$repositories[ $k ]->unsatisfied_dependencies = entrepot_get_repository_dependencies( (array) $repositories[ $k ]->dependencies );
		} else {
			$repositories[ $k ]->unsatisfied_dependencies = array();
		}

		$repositories[ $k ]->description = (object) array_map( 'entrepot_sanitize_repository_text', (array) $repositories[ $k ]->description );

		$data = install_plugin_install_status( $repository );
		foreach ( $data as $kd => $kv ) {
			$repositories[ $k ]->{$kd} = $kv;
		}

		$repositories[ $k ]->more_info = sprintf( __( 'Plus d\'informations sur %s', 'entrepot' ), $repositories[ $k ]->name );
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

	return $repositories;
}

/**
 * WP Ajax is overused by plugins.. Let's be sure we are
 * alone to request there.
 *
 * @since 1.0.0
 *
 * @return string JSON reply.
 */
function entrepot_admin_send_json() {
	if ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'update_plugins' ) ) {
		wp_send_json( __( 'Vous n\'êtes pas autorisé à réaliser cette action.', 'entrepot' ), 403 );
	}

	$repositories = entrepot_admin_get_repositories_list();

	if ( empty( $repositories ) ) {
		wp_send_json( __( 'Un problème est survenu lors de la récupération des dépôts de plugin.', 'entrepot' ), 500 );
	}

	wp_send_json( $repositories, 200 );
}

/**
 * Register the Repositories Administration page
 *
 * @since 1.0.0
 */
function entrepot_admin_add_menu() {
	$entrepot = entrepot();

	// Get upgrade tasks if any.
	$entrepot->upgrades = entrepot_get_upgrader_tasks();

	// Init an empty array for admin screens
	$screens = array();

	// The admin screen should not be displayed in subsites.
	if ( ! is_multisite() || 'network_admin_menu' === current_action() ) {
		$screens['admin'] = array(
			'page_hook' => add_plugins_page(
				__( 'Dépôts', 'entrepot' ),
				__( 'Dépôts', 'entrepot' ),
				'install_plugins',
				'repositories',
				'entrepot_admin_menu'
			),
			'load_callback' => 'entrepot_admin_send_json',
		);
	}

	if ( $entrepot->upgrades ) {
		$screens['upgrades'] = array(
			'page_hook' =>add_plugins_page(
				__( 'Mise à niveau des Extensions', 'entrepot' ),
				__( 'Mettre à niveau', 'entrepot' ),
				'install_plugins',
				'upgrade-repositories',
				'entrepot_admin_upgrade'
			),
			'load_callback' => 'entrepot_admin_upgrade_load',
		);
	}

	if ( empty( $screens ) ) {
		return;
	}

	foreach ( $screens as $screen ) {
		add_action( 'load-' . $screen['page_hook'], $screen['load_callback'] );
	}
}

/**
 * The Repositories Administration page
 *
 * @since 1.0.0
 */
function entrepot_admin_menu() {}

/**
 * Removes the Repositories Administration page from the Admin Menu.
 *
 * @since 1.0.0
 */
function entrepot_admin_head() {
	remove_submenu_page( 'plugins.php', 'repositories' );

	if ( ! entrepot()->upgrades ) {
		remove_submenu_page( 'plugins.php', 'upgrade-repositories' );
	}
}

/**
 * Register Administration scripts.
 *
 * @since 1.0.0
 */
function entrepot_admin_register_scripts() {
	wp_register_script(
		'entrepot',
		sprintf( '%1$sentrepot%2$s.js', entrepot_js_url(), entrepot_min_suffix() ),
		array( 'wp-backbone' ),
		entrepot_version(),
		true
	);

	wp_localize_script( 'entrepot', 'entrepotl10n', array(
		'url'          => esc_url_raw( add_query_arg( 'page', 'repositories', self_admin_url( 'plugins.php' ) ) ),
		'locale'       => get_user_locale(),
		'defaultIcon'  => esc_url_raw( entrepot_assets_url() . 'repo.svg' ),
		'byAuthor'     => _x( 'De %s', 'plugin', 'entrepot' ),
	) );

	wp_register_script(
		'entrepot-notices',
		sprintf( '%1$snotices%2$s.js', entrepot_js_url(), entrepot_min_suffix() ),
		array( 'common' ),
		entrepot_version(),
		true
	);

	wp_register_script(
		'entrepot-upgrader',
		sprintf( '%1$supgrader%2$s.js', entrepot_js_url(), entrepot_min_suffix() ),
		array( 'wp-backbone' ),
		entrepot_version(),
		true
	);

	wp_register_style(
		'entrepot-notices',
		sprintf( '%1$snotices%2$s.css', entrepot_assets_url(), entrepot_min_suffix() ),
		array( 'common' ),
		entrepot_version(),
		'all'
	);

	wp_register_style(
		'entrepot-upgrader',
		sprintf( '%1$supgrader%2$s.css', entrepot_assets_url(), entrepot_min_suffix() ),
		array(),
		entrepot_version(),
		'all'
	);
}

/**
 * Adds a new tab to the Plugins Install screen.
 *
 * @since 1.0.0
 *
 * @param  array  $tabs The list of tabs.
 * @return array        The list of tabs.
 */
function entrepot_admin_repositories_tab( $tabs = array() ) {
	return array_merge( $tabs, array( 'entrepot_repositories' => __( 'Entrepôt', 'entrepot' ) ) );
}

/**
 * Sets specific query args for the Entrepôt's Tab.
 *
 * @since 1.0.0
 *
 * @param  array  $args Query arguments.
 * @return array        Query arguments.
 */
function entrepot_admin_repositories_tab_args( $args = false ) {
	return array( 'entrepot' => true, 'per_page' => 0 );
}

/**
 * Temporary private function to fix WordPress Plugin Updates count
 * on the Plugin Install screen.
 *
 * @see  https://core.trac.wordpress.org/ticket/41407
 *
 * @since  1.1.0
 */
function _entrepot_admin_fix_plugin_updates_count() {
	$updates = get_site_transient( 'update_plugins' );

	if ( empty( $updates->response ) ) {
		return;
	}

	$installed_repositories = array_keys( entrepot_get_installed_repositories() );
	$repository_updates     = array();
	$js_plugins             = array_fill_keys(
		array( 'all', 'search', 'active', 'inactive', 'recently_activated', 'mustuse', 'dropins' ),
		array()
	);

	if ( ! empty( $updates->checked ) ) {
		$js_plugins['all'] = array_keys( $updates->checked );
	}

	foreach ( $updates->response as $update ) {
		$js_plugins['upgrade'][] = $update->plugin;
	}

	$repository_updates = array_intersect( $js_plugins['upgrade'], $installed_repositories );

	if ( empty( $repository_updates ) ) {
		return;
	}

	if ( empty( $js_plugins['all'] ) ) {
		$js_plugins['all'] = $js_plugins['upgrade'];
	}

	wp_localize_script( 'updates', '_wpUpdatesItemCounts', array(
		'plugins' => $js_plugins,
		'totals'  => wp_get_update_data(),
	) );
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
function entrepot_repositories_api( $res = false, $action = '', $args = null ) {
	if ( 'query_plugins' === $action && ! empty( $args->entrepot ) ) {
		wp_enqueue_script( 'entrepot' );
		$res = (object) array(
			'plugins' => array(),
			'info'    => array( 'results' => 0 ),
		);

		/**
		 * A JavaScript Error can be thrown by WordPress as some plugin
		 * informations are not localized. Untill the upstream ticket is not fixed
		 * we are making sure Upgrading from the Entrepôt tab is not throwing this
		 * error.
		 */
		_entrepot_admin_fix_plugin_updates_count();
	} elseif ( 'plugin_information' === $action && ! empty( $args->slug ) ) {
		$json = entrepot_get_repository_json( $args->slug );

		if ( $json && $json->releases ) {
			$res = entrepot_get_plugin_latest_stable_release( $json->releases, array(
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
function entrepot_admin_repositories_print_templates() {
	?>
	<form id="plugin-filter" method="post">
		<div class="wp-list-table widefat plugin-install">
			<h2 class="screen-reader-text"><?php esc_html_e( 'Liste des dépôts', 'entrepot' ); ?></h2>
			<div id="the-list" data-list="entrepot"></div>
		</div>
	</form>

	<script type="text/html" id="tmpl-entrepot-repository">
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
				<# if ( data.status && ! data.unsatisfied_dependencies.length ) { #>
					<li>
						<# if ( 'install' === data.status && data.url ) { #>
							<a class="install-now button" data-slug="{{data.slug}}" href="{{{data.url}}}" aria-label="<?php esc_attr_e( 'Installer maintenant', 'entrepot' ); ?>" data-name="{{data.name}}"><?php esc_html_e( 'Installer', 'entrepot' ); ?></a>

						<# } else if ( 'update_available' === data.status && data.url ) { #>
							<a class="update-now button aria-button-if-js" data-plugin="{{data.file}}" data-slug="{{data.slug}}" href="{{{data.url}}}" aria-label="<?php esc_attr_e( 'Mettre à jour maintenant', 'entrepot' ); ?>" data-name="{{data.name}}"><?php esc_html_e( 'Mettre à jour', 'entrepot' ); ?></a>

						<# } else if ( data.activate_url ) { #>
							<a href="{{{data.activate_url}}}" class="button button-primary activate-now" aria-label="<?php echo is_network_admin() ? esc_attr__( 'Activer sur le réseau', 'entrepot' ) : esc_attr__( 'Activer', 'entrepot' ); ?>"><?php echo is_network_admin() ? esc_html__( 'Activer sur le réseau', 'entrepot' ) : esc_html__( 'Activer', 'entrepot' ); ?></a>

						<# } else if ( data.active ) { #>
							<button type="button" class="button button-disabled" disabled="disabled"><?php esc_html_e( 'Actif', 'entrepot' ); ?></button>

						<# } else { #>
							<button type="button" class="button button-disabled" disabled="disabled"><?php esc_html_e( 'Installé', 'entrepot' ); ?></button>

						<# } #>
					</li>
				<# } #>
					<li>
						<a href="{{{data.info_url}}}" class="thickbox open-plugin-details-modal" aria-label="{{data.more_info}}" data-title="{{data.name}}"><?php esc_html_e( 'Plus de détails', 'entrepot' ); ?></a>
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

		<# if ( data.unsatisfied_dependencies.length ) { #>
			<div class="plugin-card-bottom">
				<div class="column-downloaded">
					<?php esc_html_e( 'Dépendance(s) insatisfaite(s):', 'entrepot' ); ?>
				</div>
				<div class="column-compatibility">
					<ul style="margin: 0">
						<# _.each( data.unsatisfied_dependencies, function( dependency ) { #>
							<li style="text-align: left"><strong>{{ dependency }}</strong></li>
						<# } ); #>
					</ul>
				</div>
			</div>
		<# } #>

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
function entrepot_admin_repository_information() {
	global $tab;

	if ( empty( $_REQUEST['plugin'] ) ) {
		return;
	}

	$plugin = wp_unslash( $_REQUEST['plugin'] );
	$output = array(
		'title'   => __( 'Détails du dépôt', 'entrepot' ),
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
		$output['title']   = __( 'Détails de la mise à jour', 'entrepot' );
		$output['context'] = 'repository_upgrade_notice';

		if ( ! empty( $repository->full_upgrade_notice ) ) {
			$upgrade_info = html_entity_decode( $repository->full_upgrade_notice, ENT_QUOTES, get_bloginfo( 'charset' ) );
			$output['text'] = $upgrade_info;
			$output['type'] = 'success';

		} else {
			$output['text'] = __( 'Désolé ce dépôt n\'a pas inclu d\'informations de mise à jour pour cette version.', 'entrepot' );
		}
	} else {
		$repository_data = entrepot_get_repository_json( $plugin );

		if ( ! $repository_data ) {
			return;
		}

		$output['text'] = __( 'Désolé, les détails concernant ce dépôt ne sont pas disponibles pour le moment.', 'entrepot' );
		$has_readme     = false;

		if ( ! empty( $repository_data->README ) ) {
			$request  = wp_remote_get( $repository_data->README, array(
				'timeout'    => 30,
				'user-agent' => 'Entrepôt/WordPress-Plugin-Updater; ' . get_bloginfo( 'url' ),
			) );

			if ( ! is_wp_error( $request ) && 200 === (int) wp_remote_retrieve_response_code( $request ) ) {
				$repository_info = wp_remote_retrieve_body( $request );
				$parsedown       = new Parsedown();
				$output['text']  = $parsedown->text( $repository_info );
				$output['type']  = 'success';
			}
		}
	}

	wp_enqueue_style( 'entrepot',
		sprintf( '%1$sstyle%2$s.css', entrepot_assets_url(), entrepot_min_suffix() ),
		array( 'common' ),
		entrepot_version()
	);
	iframe_header( strip_tags( $output['title'] ) ); ?>

	<div id="plugin-information-scrollable" class="entrepot">
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
			echo apply_filters( 'entrepot_repository_modal_content', $output['text'], $output );
		else :
			printf( '<div id="message" class="error"><p>%s</p></div>', esc_html( $output['text'] ) );
		endif ; ?>

		</div>
	</div>

	<?php if ( ! empty( $repository_data->issues ) ) :
		$base_url = str_replace( 'issues', '', rtrim( $repository_data->issues, '/' ) );
		$imathieu = 'https://imathi.eu/entrepot/';

		if ( 'fr_FR' !== get_locale() ) {
			$imathieu = 'https://imathi.eu/entrepot/en-us/';
		}

		$flag_url = add_query_arg( 'repository', $plugin, $imathieu );
	?>
		<div id='<?php echo esc_attr( $tab ); ?>-footer'>
			<a class="button button-primary right" href="<?php echo esc_url( $repository_data->issues ); ?>" target="_blank"><?php esc_html_e( 'Rapporter une anomalie', 'entrepot' ); ?></a>
			<a class="button button-secondary" href="<?php echo esc_url( $base_url ); ?>" target="_blank"><?php esc_html_e( 'Voir sur Github', 'entrepot' ); ?></a>
			<a class="button button-secondary" href="<?php echo esc_url( $base_url . 'pulls' ); ?>" target="_blank"><?php esc_html_e( 'Contribuer', 'entrepot' ); ?></a>
			<a class="button button-primary entrepot-warning" href="<?php echo esc_url( $flag_url ); ?>#respond" target="_blank"><?php esc_html_e( 'Signaler', 'entrepot' ); ?></a>
		</div>
	<?php endif ; ?>

	<?php iframe_footer();
	exit;
}

/**
 * Adds the detailed informations for repositories on Plugins main screen.
 *
 * @since 1.0.0
 *
 * @param  array  $plugins The plugins list.
 * @return array           The plugins list.
 */
function entrepot_all_installed_repositories_list( $plugins = array() ) {
	$repositories = entrepot_get_installed_repositories();

	if ( ! empty( $repositories ) ) {
		foreach ( array_keys( $repositories ) as $plugin_id ) {
			if ( ! isset( $plugins[ $plugin_id ] ) ) {
				continue;
			}

			$slug = sanitize_file_name( dirname( $plugin_id ) );

			// It's not a repository.
			if ( ! entrepot_get_repositories( $slug ) ) {
				continue;
			}

			/**
			 * Simply by adding the plugin's slug, the detailed informations
			 * thickbox link will be output.
			 */
			$plugins[ $plugin_id ]['slug'] = $slug;
		}
	}

	return $plugins;
}

/**
 * Remove the Activate action links if dependencies are unsatisfied for a repository.
 *
 * @since 1.1.0
 *
 * @param  array  $actions     The list of available actions for a plugin row.
 * @param  string $plugin_file Path to the plugin file relative to the plugins directory.
 * @param  array  $plugin_data An array of plugin data.
 * @return array               The list of available actions for a plugin row.
 */
function entrepot_plugin_action_links( $actions = array(), $plugin_file = '', $plugin_data = array() ) {
	if ( empty( $plugin_data['GitHub Plugin URI'] ) || empty( $plugin_data['slug'] ) ) {
		return $actions;
	}

	$repository = entrepot_get_repositories( $plugin_data['slug'] );

	if ( empty( $repository->dependencies ) ) {
		return $actions;
	}

	$dependencies = entrepot_get_repository_dependencies( $repository->dependencies );
	if ( $dependencies ) {
		entrepot()->miss_deps[ $plugin_data['slug'] ] = $dependencies;
		unset( $actions['activate'] );
	}

	return $actions;
}

/**
 * Add a new meta to inform about unsatisfied dependencies for a repository.
 *
 * @since 1.1.0
 *
 * @param  array  $plugin_meta An array of the plugin's metadata.
 * @param  string $plugin_file Path to the plugin file relative to the plugins directory.
 * @param  array  $plugin_data An array of plugin data.
 * @return array               An array of the plugin's metadata.
 */
function entrepot_plugin_row_meta( $plugin_meta = array(), $plugin_file = '', $plugin_data = array() ) {
	if ( empty( $plugin_data['GitHub Plugin URI'] ) || empty( $plugin_data['slug'] ) ) {
		return $plugin_meta;
	}

	$entrepot = entrepot();

	if ( ! isset( $entrepot->miss_deps[ $plugin_data['slug'] ] ) ) {
		return $plugin_meta;
	}

	return array_merge( $plugin_meta, array(
		'entrepot_dependencies' => sprintf( '<span class="attention"> %1$s</span> %2$s.',
			esc_html__( 'Dépendance(s) insatisfaite(s):', 'entrepot' ),
			join( ', ', $entrepot->miss_deps[ $plugin_data['slug'] ] )
		),
	) );
}

/**
 * Catches the Plugins admin notices to move them into the Notices center.
 *
 * @since 1.1.0
 */
function entrepot_catch_all_notices() {
	if ( empty( $GLOBALS['wp_filter'] ) ) {
		return;
	}

	$notice_actions = array_filter( array(
		'network_admin_notices' => is_network_admin(),
		'user_admin_notices'    => is_user_admin(),
		'admin_notices'         => true,
		'all_admin_notices'     => true,
	) );

	$registered_notices = array_intersect_key( $GLOBALS['wp_filter'], $notice_actions );
	$core_hooks = array_fill_keys( array(
		'update_nag',
		'default_password_nag',
		'maintenance_nag',
		'new_user_email_admin_notice',
		'site_admin_notice'
	), 0 );

	foreach ( $registered_notices as $hook_key => $priorities ) {
		foreach ( $priorities->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $hook_name => $hook_data ) {
				if ( isset( $core_hooks[ $hook_name ] ) ) {
					continue;
				} else {
					// Remove the action on the core hook
					remove_action( $hook_key, $hook_name, $priority );

					// Add it to the entrepôt hook
					add_action( 'entrepot_notices', $hook_name, $priority );
				}
			}
		}
	}

	ob_start();

	do_action( 'entrepot_notices' );

	$notices = ob_get_clean();
	$entrepot_notices  = array_fill_keys( array( 'upgrade', 'error', 'updated' ), array() );

	// Counts
	$all_notices_count     = 0;
	$upgrade_notices_count = 0;
	$updated_notices_count = 0;
	$error_notices_count   = 0;

	if ( ! empty( $notices ) ) {
		$notices = str_replace( array("<p>", "</p>" ), '',	$notices );
		preg_match_all( '/\s*<div.*class=\"(.*?)\"[.*|>]\s*(.*?)\s*<\/div>/', trim( $notices, "\n\t" ), $results );

		if ( empty( $results[1] ) || empty( $results[2] ) ) {
			return;
		}

		$allowed_tags      = wp_kses_allowed_html( 'entrepot' );
		$allowed_tags['p'] = true;

		foreach ( $results[1] as $kt => $type ) {
			$classes = explode( ' ', $type );
			$text    = wp_kses( sprintf( '<p>%s</p>', $results[2][ $kt ] ), $allowed_tags );

			if ( in_array( 'update-nag', $classes, true ) ) {
				$entrepot_notices['upgrade'][] = $text;
				$upgrade_notices_count += 1;
			} else if ( in_array( 'error', $classes, true ) ) {
				$entrepot_notices['error'][] = $text;
				$error_notices_count += 1;
			}  else if ( in_array( 'updated', $classes, true ) ) {
				$entrepot_notices['updated'][] = $text;
				$updated_notices_count += 1;
			}

			$all_notices_count += 1;
		}
	}

	$repository_upgrades_count = count( entrepot()->upgrades );

	// Only display the notice to people who can access the screen.
	if ( $repository_upgrades_count && current_user_can( 'install_plugins' ) ) {
		array_push( $entrepot_notices['upgrade'], sprintf( '<p>%1$s. %2$s</p>',
			sprintf( _n( '%d extension nécessite une mise à niveau', '%d extensions nécessitent une mise à niveau.', $repository_upgrades_count, 'entrepot' ), $repository_upgrades_count ),
			sprintf( __( 'Merci de visiter la page d\'administration des %s pour effecuer les opérations nécessaires.', 'entrepot' ), sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( add_query_arg( 'page', 'upgrade-repositories', self_admin_url( 'plugins.php' ) ) ),
				__( 'mises à niveau', 'entrepot' )
			) )
		) );

		$upgrade_notices_count += 1;
		$all_notices_count     += 1;
	}

	if ( ! $all_notices_count ) {
		return;
	}

	wp_enqueue_style( 'entrepot-notices' );
	wp_enqueue_script ( 'entrepot-notices' );
	wp_localize_script( 'entrepot-notices', 'entrepotNoticesl10n', array(
		'strings' => array(
			'tabTitle' => sprintf( _n( '<span class="count">%d</span> Alerte', '<span class="count">%d</span> Alertes', $all_notices_count, 'entrepot' ), $all_notices_count ),
			'tabLiTitles' => array(
				'upgrade' => sprintf(
					'<span class="count">%1$d</span> <span class="text">%2$s</span>',
					$upgrade_notices_count,
					_nx( 'Mise à niveau', 'Mises à niveau', $upgrade_notices_count, 'Admin Notices Center tab', 'entrepot' )
				),
				'updated' => sprintf(
					'<span class="count">%1$d</span> <span class="text">%2$s</span>',
					$updated_notices_count,
					_nx( 'Info', 'Infos', $updated_notices_count, 'Admin Notices Center tab', 'entrepot' )
				),
				'error'   => sprintf(
					'<span class="count">%1$d</span> <span class="text">%2$s</span>',
					$error_notices_count,
					_nx( 'Erreur', 'Erreurs', $error_notices_count, 'Admin Notices Center tab', 'entrepot' )
				),
			),
			'trash' => __( 'Ne plus afficher cette alerte', 'entrepot' ),
		),
		'notices' => $entrepot_notices,
	) );
}

/**
 * Use a new notice to show the user the Notices center.
 *
 * @since 1.1.0
 */
function entrepot_about_notices_center() {
	if ( ! get_site_transient( 'entrepot_notice_example' ) ) {
		return;
	}
	?>
	<div id="message" class="updated">
		<p>
			<?php esc_html_e( 'Voici votre nouveau gestionnaires d\'alertes. Toutes vos prochaines alertes s\'y afficheront. Les alertes d\'information ou d\'erreur peuvent être définitivement supprimée grâce à l\'icône poubelle. Prenez garde, malgré tout, à ne supprimer que ce que vous considérez comme étant des éléments indésirables.', 'entrepot' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'all_admin_notices', 'entrepot_about_notices_center' );

/**
 * Check the request headers of the Plugins Upgrade screen before sending the right reply.
 *
 * @since 1.1.0
 */
function entrepot_admin_upgrade_load() {
	$headers = array_intersect_key( apache_request_headers(), array(
		'Accept'           => true,
		'X-Entrepot-Nonce' => true,
	) );

	// It's a regular Administration screen display.
	if ( 'application/json' !== $headers['Accept'] || ! isset( $headers['X-Entrepot-Nonce'] ) ) {
		return;
	}

	$error = array(
		'message'   => __( 'La mise à niveau a été interrompue en raison d\'une erreur.', 'entrepot' ),
		'type'      => 'error',
	);

	if ( empty( $_POST['id'] ) || ! isset( $_POST['count'] ) || ! isset( $_POST['done'] ) ) {
		wp_send_json_error( $error );
	}

	// Add the action to the error
	$callback          = sanitize_key( $_POST['id'] );
	$error['callback'] = $callback;

	if ( ! current_user_can( 'update_plugins' ) || ! wp_verify_nonce( $headers['X-Entrepot-Nonce'], 'entrepot-upgrader' ) ) {
		$error['message'] = __( 'Vous n\'êtes pas autorisé à réaliser cette mise à niveau.', 'entrepot' );
		wp_send_json_error( $error );
	}

	if ( ! is_callable( $callback ) ) {
		$error['message'] = __( 'La fonction de mise à niveau pour cette tâche est inexistante.', 'entrepot' );
		wp_send_json_error( $error );
	}

	$number = 20;
	if ( ! empty( $_POST['number'] ) ) {
		$number = (int) $_POST['number'];
	}

	$did = call_user_func_array( $callback, array( $number ) );

	wp_send_json_success( array( 'done' => $did, 'callback' => $callback ) );
}

/**
 * Plugins upgrade screen.
 *
 * @since 1.1.0
 */
function entrepot_admin_upgrade() {
	$tasks    = array();
	$infos    = array();
	$entrepot = entrepot();

	foreach ( $entrepot->upgrades as $kr => $repo_tasks ) {
		if ( empty( $repo_tasks['tasks'] ) ) {
			continue;
		}

		if ( empty( $repo_tasks['info'] ) ) {
			continue;
		} else {
			$infos[] = array_merge( $repo_tasks['info'], array(
				'description' => sprintf( _n( '%d tâche de mise à niveau à effectuer.', '%d tâches de mise à niveau à effectuer.', count( $repo_tasks['tasks'] ), 'entrepot' ), count( $repo_tasks['tasks'] ) ),
			) );
		}

		foreach ( $repo_tasks['tasks'] as $repo_task ) {
			if ( empty( $repo_task['count'] ) || ! is_callable( $repo_task['count'] ) ) {
				continue;
			}

			$repo_task['count'] = (int) call_user_func( $repo_task['count'] );

			// If nothing needs to be ugraded, remove the task.
			if ( empty( $repo_task['count'] ) ) {
				continue;
			}

			$tasks[ $kr ][ $repo_task['callback'] ]            = $repo_task;
			$tasks[ $kr ][ $repo_task['callback'] ]['message'] = $repo_task['message'];
		}
	}

	if ( ! empty( $tasks ) && ! empty( $infos ) ) {
		wp_enqueue_style  ( 'entrepot-upgrader' );
		wp_enqueue_script ( 'entrepot-upgrader' );
		wp_localize_script( 'entrepot-upgrader', 'entrepotUpgraderl10n', array(
			'tasks'          => array_map( 'array_values', $tasks ),
			'repositories'   => $infos,
			'entrepot_nonce' => wp_create_nonce( 'entrepot-upgrader' ),
			'upgraded'       => __( 'Mis à niveau', 'entrepot' ),
			'confirm'        => __( 'Attention, vous ne pourrez pas faire machine arrière à moins d\'avoir fait une sauvegarde de votre base de données. Souhaitez-vous poursuivre ?', 'entrepot' ),
		) );
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( _n( 'Mise à niveau', 'Mises à niveau', count( $infos ), 'entrepot' ) ); ?></h1>

		<?php if ( empty( $tasks ) || empty( $infos ) ) : ?>
			<p class="description"><?php esc_html_e( 'Aucune opération de mise à niveau à réaliser.', 'entrepot' ); ?></p>

		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Une fois votre base de données sauvegardée, utilisez le bouton de Mise à niveau pour procéder aux opérations correspondantes.', 'entrepot' ); ?></p>

			<div id="entrepot-cards"></div>

			<script type="text/html" id="tmpl-repository-card">
				<div class="repository-info">
					<# if ( data.icon ) { #>
						<img src="{{data.icon}}" width="100px" height="100px">
					<# } #>

					<h2>{{data.name}}</h2>
					<p class="description">{{data.description}}</p>

					<button type="button" class="button secondary button repository-do-upgrade" data-slug="{{data.slug}}">
						<?php esc_html_e( 'Mettre à niveau', 'entrepot' ); ?>
					</button>
				</div>
				<div class="repository-tasks"></div>
			</script>

			<script type="text/html" id="tmpl-progress-window">
				<div id="{{data.id}}">
					<div class="task-description">{{data.message}}</div>
					<div class="upgrade-progress">
						<div class="upgrade-bar"></div>
					</div>
				</div>
			</script>

		<?php endif ; ?>
	</div>
	<?php
}
