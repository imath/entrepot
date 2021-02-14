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
	$db_version = entrepot_db_version();
	$version    = entrepot_version();

	if ( ! version_compare( $db_version, $version, '<' ) ) {
		return;
	}

	if ( 1.1 === (float) $version ) {
		set_site_transient( 'entrepot_notice_example', true, DAY_IN_SECONDS );
	}

	// New repositories can be added each time the Entrepôt has a new release.
	if ( 1.4 >= (float) $version ) {
		wp_cache_delete( 'plugins', 'entrepot' );
		wp_cache_delete( 'themes', 'entrepot' );
	} else {
		wp_cache_delete( 'repositories', 'entrepot' );
	}

	if ( ! $db_version || 1.5 > (float) $db_version ) {
		$blocks_dir = entrepot_blocks_dir();
		$index_file = trailingslashit( $blocks_dir ) . 'index.php';

		// Create the wp-content/blocks directory.
		if ( ! file_exists( $index_file ) && wp_mkdir_p( $blocks_dir ) ) {
			$f = fopen( $index_file, 'w' );
			fwrite( $f, "<?php\n// Silence is golden.\n" );
			fclose( $f );
		}
	}

	// Update Entrepôt version.
	update_network_option( 0, '_entrepot_version', $version );
}

/**
 * Gets the list of available plugin repositories.
 *
 * @since 1.4.0
 *
 * @return array The list of available plugin repositories.
 */
function entrepot_admin_get_plugin_repositories_list() {
	$repositories    = entrepot_get_repositories();
	$installed_repos = entrepot_get_installed_repositories();
	$updates_require = entrepot_admin_check_plugin_requires( true );
	$keyed_by_slug   = array();

	foreach ( $installed_repos as $i => $installed_repo ) {
		$keyed_by_slug[ entrepot_get_repository_slug( $i ) ] = $installed_repo;
	}

	if ( ! function_exists( 'install_plugin_install_status' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	}

	$thickbox_link = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=%s&amp;TB_iframe=true&amp;width=600&amp;height=550' );

	foreach ( $repositories as $k => $repository ) {
		$data            = null;
		$plugin_requires = array();

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
		} elseif ( 'update_available' === $data['status'] && isset( $updates_require['data'][ $data['file'] ] ) ) {
			$plugin_requires = $updates_require['data'][ $data['file'] ];

			if ( isset( $plugin_requires['php'] ) && $plugin_requires['php'] ) {
				$repositories[ $k ]->requires_php         = $plugin_requires['php'];
				$repositories[ $k ]->requires_php_message = str_replace( 'phpVersion', $plugin_requires['php'], $updates_require['warnings']['PHP'] );
			}

			if ( isset( $plugin_requires['wp'] ) && $plugin_requires['wp'] ) {
				$repositories[ $k ]->requires_wp         = $plugin_requires['wp'];
				$repositories[ $k ]->requires_wp_message = str_replace( 'wpVersion', $plugin_requires['wp'], $updates_require['warnings']['WP'] );
			}
		}
	}

	return $repositories;
}

/**
 * Gets the list of available theme repositories.
 *
 * @since 1.4.0
 *
 * @return array The list of available theme repositories.
 */
function entrepot_admin_get_theme_repositories_list() {
	$themes = entrepot_get_repositories( '', 'themes' );

	// No Themes in the Entrepôt, stop!
	if ( ! $themes ) {
		return array();
	}

	// Prepare a list of installed themes to check against before the loop.
	$installed_themes = array();
	$wp_themes        = wp_get_themes();
	foreach ( $wp_themes as $theme ) {
		$installed_themes[] = $theme->get_stylesheet();
	}
	$update_php = network_admin_url( 'update.php?action=install-theme' );
	$locale     = get_user_locale();

	// Set up properties for themes available in the Entrepôt.
	foreach ( $themes as &$theme ) {
		$theme->install_url = add_query_arg(
			array(
				'theme'       => $theme->slug,
				'_wpnonce'    => wp_create_nonce( 'install-theme_' . $theme->slug ),
			), $update_php
		);

		// For testing purpose.
		$theme->descriptions = $theme->description;

		/**
		 * Defaults to en_US if User's language translation
		 * is not included in Theme's JSON.
		 */
		if ( ! isset( $theme->description->{$locale} ) ) {
			$locale = 'en_US';
		}

		$theme->name        = wp_kses( $theme->name, array() );
		$theme->version     = '';
		$theme->description = entrepot_sanitize_repository_text( $theme->description->{$locale} );
		$theme->stars       = '';
		$theme->num_ratings = 0;
		$theme->preview_url = '';

		if ( ! empty( $theme->urls->preview_url ) ) {
			$theme->preview_url = set_url_scheme( $theme->urls->preview_url );
			$theme->hasPreview  = true;
		} else {
			$theme->preview_url = add_query_arg(
				array(
					'page'        => 'repositories',
					'theme'       => $theme->slug,
				), network_admin_url( 'themes.php' )
			);
		}

		// Handle themes that are already installed as installed themes.
		if ( in_array( $theme->slug, $installed_themes, true ) ) {
			$theme->type    = 'installed';
			$theme->version = $wp_themes[ $theme->slug ]->get( 'Version' );
		} else {
			$theme->type = 'entrepot';
		}

		// Set active based on customized theme.
		$theme->active = ( isset( $_POST['customized_theme'] ) && $_POST['customized_theme'] === $theme->slug );

		// Map available theme properties to installed theme properties.
		$theme->id             = $theme->slug;
		$theme->screenshot_url = $theme->screenshot;
		$theme->screenshot     = array( $theme->screenshot );
		$theme->authorAndUri   = wp_kses( $theme->author, array() );
		$theme->author         = array(
			'display_name' => $theme->authorAndUri,
		);

		if ( ! empty( $theme->template ) ) {
			$theme->parent = $theme->template;
		} else {
			$theme->parent = false;
		}

		$theme->requires     = false;
		$theme->requires_php = '5.6';
		if ( isset( $theme->requirements->WP ) ) {
			$theme->requires = $theme->requirements->WP;
		}

		if ( isset( $theme->requirements->PHP ) ) {
			$theme->requires_php = $theme->requirements->PHP;
		}

		$theme->compatibleWP  = is_wp_version_compatible( $theme->requires );
		$theme->compatiblePHP = is_php_version_compatible( $theme->requires_php );

		if ( ! defined( 'PR_TESTING_ASSETS' ) ) {
			foreach ( array( 'descriptions', 'country', 'releases', 'issues', 'README', 'urls', 'requirements' ) as $rk ) {
				unset( $theme->{$rk} );
			}
		}
	}

	return $themes;
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

	$repositories = entrepot_admin_get_plugin_repositories_list();

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
		$screens = array(
			'add-plugins' => array(
				'page_hook' => add_plugins_page(
					__( 'Dépôts', 'entrepot' ),
					__( 'Dépôts', 'entrepot' ),
					'install_plugins',
					'repositories',
					'entrepot_admin_menu'
				),
				'load_callback' => 'entrepot_admin_send_json',
			),
			'theme-details' => array(
				'page_hook' => add_submenu_page(
					'themes.php',
					__( 'Dépôts', 'entrepot' ),
					__( 'Dépôts', 'entrepot' ),
					'install_themes',
					'repositories',
					'entrepot_admin_menu'
				),
				'load_callback' => 'entrepot_admin_theme_details',
			),
		);
	}

	if ( $entrepot->upgrades ) {
		$screens['upgrades'] = array(
			'page_hook' => add_plugins_page(
				__( 'Mise à niveau des Extensions', 'entrepot' ),
				__( 'Mettre à niveau', 'entrepot' ),
				'install_plugins',
				'upgrade-repositories',
				'entrepot_admin_upgrade'
			),
			'load_callback' => 'entrepot_admin_upgrade_load',
		);
	}

	$screens['entrepot_plugins'] = array(
		'page_hook' => add_menu_page(
			__( 'Gestion des extensions de l’Entrepôt', 'entrepot' ),
			__( 'Extensions', 'entrepot' ),
			'install_plugins',
			'entrepot',
			'entrepot_admin_menu',
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+Cjxzdmcgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgeG1sbnM6c2VyaWY9Imh0dHA6Ly93d3cuc2VyaWYuY29tLyIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkO2NsaXAtcnVsZTpldmVub2RkO3N0cm9rZS1saW5lam9pbjpyb3VuZDtzdHJva2UtbWl0ZXJsaW1pdDoyOyI+CiAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLC0wLjA3LDAuMTAwNzg4KSI+CiAgICAgICAgPHBhdGggZD0iTTMuNTA1LDQ5Ljc0OUw1MC4wNTUsNy45NzlMOTYuNjM1LDQ5Ljc0OUM5Ny43NjQsNTAuNzI5IDk3Ljg4Miw1Mi41MTEgOTYuODQzLDUzLjY5OEM5NS44MzQsNTQuNzk2IDk0LjA1Myw1NC45MTUgOTIuODk1LDUzLjg0Nkw1MC4wNTUsMTUuNDg5TDcuMjE2LDUzLjg0N0M2LjA4OCw1NC45MTUgNC4zMDcsNTQuNzk2IDMuMjY4LDUzLjY5OUMyLjI1OCw1Mi41MTEgMi4zMTgsNTAuNzI5IDMuNTA1LDQ5Ljc0OVoiIHN0eWxlPSJmaWxsLXJ1bGU6bm9uemVybzsiLz4KICAgIDwvZz4KICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDAuMDEyOTEyLC0wLjk5OTkxNywwLjk5OTkxNywwLjAxMjkxMiw2LjQ5MTgyLDk1LjY5OTIpIj4KICAgICAgICA8cGF0aCBkPSJNNTkuMzIxLDMwLjc2MUw1MS40LDM4LjY4NEw0Ni44MjcsMzQuMTExTDU0Ljc0OSwyNi4xODdDNTUuNjA2LDI1LjM1NyA1Ny4zMTYsMjUuNjk5IDU4LjU2NCwyNi45N0M1OS44MzcsMjguMjE3IDYwLjE3OCwyOS45MyA1OS4zMjEsMzAuNzYxWk0zOS43NiwzNS4wODhMNDEuOTg0LDMyLjM1TDY0LjAxOCw1NC4zODRMNjEuMTA4LDU2LjQzNkM1OS4zNzEsNTguMTczIDU0LjY3Niw1OS4yNzQgNTEuNzY2LDU5LjI3NEw0Mi4yNzgsNTkuMjc0TDM5LjI0Niw2Mi4zMDZDMzcuODAzLDYzLjc0OSAzNS40NzksNjMuNzQ5IDM0LjA2MSw2Mi4zMDZDMzIuNjE5LDYwLjg4NyAzMi42MTksNTguNTY1IDM0LjA2MSw1Ny4xMjFMMzcuMDkzLDU0LjA4OUwzNy4wOTMsNDQuNjAyQzM3LjA5Myw0MS44MzkgMzguMDczLDM2LjgwMSAzOS43NiwzNS4wODhaTTU3LjUxMiw0NC43OTZMNjUuNDM2LDM2Ljg3NUM2Ni4yNjYsMzYuMDE4IDY3Ljk3OCwzNi4zNjEgNjkuMjI2LDM3LjYzMkM3MC40OTcsMzguODggNzAuODQsNDAuNTkgNjkuOTgzLDQxLjQyMUw2Mi4wNjEsNDkuMzY5TDU3LjUxMiw0NC43OTZaIiBzdHlsZT0iZmlsbC1ydWxlOm5vbnplcm87Ii8+CiAgICA8L2c+CiAgICA8cGF0aCBkPSJNMzcuNjMxLDgwLjEyMkwyNC42NjIsNjcuMTUxTDI3LjQxMyw2NC40QzI4LjMzMSw2My4zNzIgMzEuNjMzLDYzLjUzNyAzMy44NTIsNjQuOTg3QzM2LjA3Miw2Ni40NTQgMzYuNDc2LDY3LjMzNSAzOS4xOTIsNjguODRDNDEuMzU2LDcwLjAxMyA0My42ODYsNzEuMTUxIDQ3LjM1NSw3MC4zOTlMMzcuNjMxLDgwLjEyMlpNMzYuMzI5LDgxLjQyNEwyMy4zNTksNjguNDU0TDIwLjExMiw3MS43MDFDMTkuMzk3LDcyLjQxNiAxOS4zOTcsNzMuNTczIDIwLjExMiw3NC4yODhMMjIuMDU3LDc2LjIzMkMyMi43NzIsNzYuOTQ4IDIyLjc3Miw3OC4xMjMgMjIuMDU3LDc4LjgzOEMyMC45NTYsNzkuOTM5IDE5LjQzNCw4MC44NzQgMTguMDAyLDgxLjkzOEMxNy4zNjEsODIuNDE0IDE2LjcxOCw4Mi45MSAxNi4xNSw4My40NzlDMTMuNjkxLDg1LjkzOCAxMS44MDEsODkuMzMxIDEzLjYzNiw5MS4xNDdDMTUuNDUyLDkyLjk4MiAxOC44NDYsOTEuMDkyIDIxLjMwNCw4OC42NTJDMjEuODc0LDg4LjA4NCAyMi4zNjksODcuNDQxIDIyLjg2Myw4Ni43OEMyMy45MSw4NS4zNSAyNC44NDUsODMuODI4IDI1Ljk2NCw4Mi43MjdDMjYuNjc5LDgyLjAxMiAyNy44MzYsODIuMDEyIDI4LjU1MSw4Mi43MjdMMzAuNDk2LDg0LjY3MUMzMS4yMTEsODUuMzg3IDMyLjM2Nyw4NS4zODcgMzMuMDgyLDg0LjY3MUwzNi4zMjksODEuNDI0WiIgc3R5bGU9ImZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMC41MTU4LDAsMCwwLjUxNTgsNDAuODQ0OSw1NS43NzYyKSI+CiAgICAgICAgPHBhdGggZD0iTTgwLDcwTDIwLDcwTDIwLDMzTDI5LDMzTDI5LDI2TDQ1LDI2TDQ1LDMzTDU3LDMzTDU3LDI2TDczLDI2TDczLDMzTDgwLDMzTDgwLDcwWiIvPgogICAgPC9nPgo8L3N2Zz4K',
			'network_admin_menu' !== current_action() ? 67 : 24
		),
	);

	$screens['entrepot_themes'] = array(
		'page_hook' => add_submenu_page(
			'entrepot',
			__( 'Gestion des thèmes de l’Entrepôt', 'entrepot' ),
			__( 'Thèmes', 'entrepot' ),
			'install_themes',
			'entrepot-themes',
			'entrepot_admin_menu',
			20
		),
	);

	$screens['entrepot_settings'] = array(
		'page_hook' => add_submenu_page(
			'entrepot',
			__( 'Réglages de l’Entrepôt', 'entrepot' ),
			__( 'Réglages', 'entrepot' ),
			'install_plugins',
			'entrepot-settings',
			'entrepot_admin_settings_page',
			100
		),
		'load_callback' => 'entrepot_admin_settings_load',
	);

	if ( empty( $screens ) ) {
		return;
	}

	foreach ( $screens as $screen ) {
		if ( ! isset( $screen['load_callback'] ) || ! $screen['load_callback'] ) {
			continue;
		}

		add_action( 'load-' . $screen['page_hook'], $screen['load_callback'] );
	}
}

/**
 * Entrepôt settings section output.
 *
 * @since 1.6.0
 */
function entrepot_admin_settings_section() {}

/**
 * Entrepôt GitHub Token setting output.
 *
 * @since 1.6.0
 */
function entrepot_admin_settings_github_personal_token() {
	$option_name  = '_entrepot_github_personal_token';
	$github_token = get_option( $option_name, '' );

	printf(
		'<input id="%1$s" name="%2$s" type="text" class="regular-text code" value="%3$s" />',
		$option_name,
		$option_name,
		esc_html( $github_token )
	);
}

/**
 * Adds Entrepôt settings.
 *
 * @since 1.6.0
 */
function entrepot_admin_settings() {
	add_settings_section(
		'entrepot_settings',
		__( 'Intégration avec GitHub.com', 'entrepot' ),
		'entrepot_admin_settings_section',
		'entrepot'
	);

	add_settings_field(
		'_entrepot_github_personal_token',
		__( 'Jeton d’accès personnel à GitHub.com', 'entrepot' ),
		'entrepot_admin_settings_github_personal_token',
		'entrepot',
		'entrepot_settings'
	);

	register_setting(
		'entrepot',
		'_entrepot_github_personal_token',
		array(
			'type'              => 'string',
			'description'       => __( 'Réglage de l’Entrepôt pour garder en base de données le jeton d’accès personnel à GitHub.com de l’Administrateur', 'entrepot' ),
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => false,
			'default'           => '',
		)
	);
}

/**
 * Entrepot settings page output.
 *
 * @since 1.6.0
 */
function entrepot_admin_settings_page() {
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Réglages de l’Entrepôt', 'entrepot' ); ?></h1>
		<hr class="wp-header-end">

		<?php settings_errors(); ?>

		<form method="POST" action="options.php">
			<?php
			settings_fields( 'entrepot' );
			do_settings_sections( 'entrepot' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * The Repositories Administration page
 *
 * @since 1.0.0
 */
function entrepot_admin_menu() {
	$preload_data = array_reduce(
		array(
			'/entrepot/v1/plugins',
		),
		'rest_preload_api_request',
		array()
	);
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Extensions de l’Entrepôt', 'entrepot' ); ?></h1>
		<hr class="wp-header-end">

		<pre>
			<?php print_r( $preload_data ); ?>
		</pre>
	</div>
	<?php
}

/**
 * Removes the Repositories Administration page from the Admin Menu.
 *
 * @since 1.0.0
 */
function entrepot_admin_head() {
	global $menu, $submenu;

	foreach ( $menu as $key => $menu_item ) {
		if ( isset( $menu_item[2] ) && 'entrepot' === $menu_item[2] ) {
			$menu[ $key ][0] = _x( 'Entrepôt', 'plugin main menu title', 'entrepot' );
		}
	}

	if ( isset( $submenu['entrepot'] ) ) {
		$settings_menu = array();

		foreach ( $submenu['entrepot'] as $sub_key => $submenu_item ) {
			if ( isset( $submenu_item[2] ) && 'entrepot-settings' === $submenu_item[2] ) {
				$settings_menu = $submenu_item;
				unset( $submenu['entrepot'][ $sub_key] );
			}
		}

		// Reorder to make sure the settings page is at last position.
		$submenu['entrepot'] = array_merge( $submenu['entrepot'], array( $settings_menu ) );
	}

	remove_submenu_page( 'plugins.php', 'repositories' );
	remove_submenu_page( 'themes.php', 'repositories' );

	if ( ! entrepot()->upgrades ) {
		remove_submenu_page( 'plugins.php', 'upgrade-repositories' );
	}
}

/**
 * Add an help tab to the Entrepot settings screen.
 *
 * @since 1.6.0
 */
function entrepot_admin_settings_load() {
	get_current_screen()->add_help_tab(
		array(
			'id'      => 'entrepot-setting-overview',
			'title'   => __( 'Vue d’ensemble', 'entrepot' ),
			'content' => '<p>' . __( 'Utilisez le champ Jeton d’accès à GitHub.com pour spécifier votre « Personal Access Token »', 'entrepot' ) . '</p>',
		)
	);
}

/**
 * Register Administration scripts.
 *
 * @since 1.0.0
 */
function entrepot_admin_register_scripts() {
	global $wp_version;

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
		'wpVersion'    => (float) $wp_version,
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

	wp_register_script(
		'entrepot-themes',
		sprintf( '%1$sthemes%2$s.js', entrepot_js_url(), entrepot_min_suffix() ),
		array( 'theme' ),
		entrepot_version(),
		true
	);

	wp_register_script(
		'entrepot-updates',
		sprintf( '%1$sentrepot-updates%2$s.js', entrepot_js_url(), entrepot_min_suffix() ),
		array( 'updates' ),
		entrepot_version(),
		true
	);

	wp_register_style(
		'entrepot-flag',
		sprintf( '%1$sflag-button%2$s.css', entrepot_assets_url(), entrepot_min_suffix() ),
		array( 'common' ),
		entrepot_version()
	);

	wp_register_style(
		'entrepot-admin',
		sprintf( '%1$sstyle%2$s.css', entrepot_assets_url(), entrepot_min_suffix() ),
		array( 'entrepot-flag' ),
		entrepot_version()
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
 * Enqueue scripts when needed.
 *
 * @since 1.4.0
 */
function entrepot_admin_enqueue_scripts() {
	$current_screen = get_current_screen();

	if ( empty( $current_screen->id ) || ( 0 !== strpos( $current_screen->id, 'theme-install' ) && 0 !== strpos( $current_screen->id, 'themes' ) ) ) {
		return;
	}

	if ( false === strpos( $current_screen->id, 'repositories' ) && 'themes-network' !== $current_screen->id ) {
		wp_enqueue_script( 'entrepot-themes' );
		wp_localize_script( 'entrepot-themes', 'entrepotl10nThemes', array(
			'tabText'  => __( 'Entrepôt', 'entrepot' ),
			'moreText' => __( 'Afficher les détails', 'entrepot' ),
			'btnText'  => __( 'Détails', 'entrepot' ),
		) );
	}

	if ( 0 === strpos( $current_screen->id, 'themes' ) ) {
		$css = sprintf( '%1$sflag-button%2$s.css', entrepot_assets_dir(), entrepot_min_suffix() );

		if ( $css && file_exists( $css ) ) {
			$css = file_get_contents( $css );

			wp_add_inline_style( 'common', sprintf( '
				.entrepot-actions {
					position: absolute;
					left: 10px;
					bottom: 5px;
				}

				%s
			', $css ) );
		}
	}
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
	// Plugins
	if ( 'query_plugins' === $action && ! empty( $args->entrepot ) ) {
		wp_enqueue_script( 'entrepot' );
		$res = (object) array(
			'plugins' => array(),
			'info'    => array( 'results' => 0 ),
		);

	// Themes
	} elseif ( 'query_themes' === $action && ! empty( $args->browse ) && 'entrepot' === $args->browse ) {
		$themes = entrepot_admin_get_theme_repositories_list();

		$res = new stdClass;
		$res->themes = $themes;
		$res->info = array( "page" => 1,"pages" => 1,"results" => count( $themes ) );

	} elseif ( 'plugin_information' === $action && ! empty( $args->slug ) ) {
		$json = entrepot_get_repository_json( $args->slug );

		if ( $json && $json->releases ) {
			$res = entrepot_get_repository_latest_stable_release( $json->releases, array(
				'plugin'            => $json->name,
				'slug'              => $args->slug,
				'Version'           => 'latest',
				'GitHub Plugin URI' => str_replace( '/releases', '', $json->releases ),
			), 'plugin' );
		}

	} elseif ( 'theme_information' === $action && ! empty( $args->slug ) ) {
		$json = entrepot_get_repository_json( $args->slug, 'themes' );

		if ( $json && $json->releases ) {
			$res = entrepot_get_repository_latest_stable_release( $json->releases, array(
				'theme'             => $json->name,
				'slug'              => $args->slug,
				'Version'           => 'latest',
				'GitHub Theme URI' => str_replace( '/releases', '', $json->releases ),
			), 'theme' );
		}
	} elseif ( 'block_information' === $action && ! empty( $args->slug ) ) {
		$json = entrepot_get_repository_json( $args->slug, 'blocks' );

		if ( $json && $json->releases ) {
			$res = entrepot_get_repository_latest_stable_release( $json->releases, array(
				'block'             => $json->name,
				'slug'              => $args->slug,
				'Version'           => 'latest',
				'GitHub Block URI' => str_replace( '/releases', '', $json->releases ),
			), 'block' );
		}
	}

	return $res;
}

/**
 * Prints the the JavaScript templates for Plugin views.
 *
 * @since 1.4.0
 *
 * @return string HTML Output.
 */
function entrepot_admin_plugins_print_templates() {
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
							<a class="update-now button aria-button-if-js" data-plugin="{{data.file}}" data-slug="{{data.slug}}" href="{{{data.url}}}" aria-label="<?php esc_attr_e( 'Mettre à jour maintenant', 'entrepot' ); ?>" data-name="{{data.name}}" <# if ( data.requires_php || data.requires_wp ) { #>disabled="disabled"<# } #>><?php esc_html_e( 'Mettre à jour', 'entrepot' ); ?></a>

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

		<# if ( data.unsatisfied_dependencies.length || data.requires_php || data.requires_wp ) { #>
			<div class="plugin-card-bottom">
				<# if ( data.unsatisfied_dependencies.length ) { #>
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
				<# } #>
				<# if ( data.requires_php || data.requires_wp ) { #>
					<div class="column-requires">
						<ul style="margin: 0">
							<# if ( data.requires_wp ) { #>
								<li style="text-align: left"><span class="attention">{{ data.requires_wp_message }}</span></li>
							<# } #>
							<# if ( data.requires_php ) { #>
								<li style="text-align: left"><span class="attention">{{ data.requires_php_message }}</span></li>
							<# } #>
						</ul>
					</div>
				<# } #>
			</div>
		<# } #>

	</script>
	<?php
}

/**
 * Return the Repositories flagging URL.
 *
 * @since 1.4.0
 *
 * @param  string $repo_name The name identifying the repository.
 * @return string            The URL to the flagging form.
 */
function entrepot_get_flag_url( $repo_name ) {
	$imathieu = 'https://imathi.eu/entrepot/#respond';

	if ( 'fr_FR' !== get_locale() ) {
		$imathieu = 'https://imathi.eu/entrepot/translate/en-us/#respond';
	}

	return add_query_arg( 'repository', $repo_name, $imathieu );
}

/**
 * Displays an iframe containing Plugin/Theme details.
 *
 * @since 1.4.0
 *
 * @param array  $args {
 *  An array of arguments.
 *
 *  @type string $type      Whether it's an iframe about plugins or themes.
 *  @type string $repo_name The GitHub repository name.
 *  @type string $title     The iframe title tag.
 *  @type string $text      The content to output.
 *  @type string $section   The displayed iframe section.
 *  @type string $context   Whether it's the repo description or the upgrade notice.
 *  @type string $result    Whether there was an error or it's a success.
 * }
 */
function entrepot_admin_repository_iframe( $args = array() ) {
	global $tab;

	$r = wp_parse_args( $args, array(
		'type'       => 'plugins',
		'repo_name'  => '',
		'repository' => null,
		'title'      => __( 'Détails du dépôt', 'entrepot' ),
		'text'       => '',
		'section'    => '',
		'context'    => 'repository_information',
		'result'     => 'error',
	) );

	if ( ! $r['text'] && 'success' !== $r['result'] ) {
		$repository_data = $r['repository'];

		$r['text'] = __( 'Désolé, les détails concernant ce dépôt ne sont pas disponibles pour le moment.', 'entrepot' );
		$sections  = array();

		if ( $repository_data ) {
			if ( ! $tab ) {
				$tab = 'plugin-information';
			}

			$uri             = '';
			$allowed_section = array(
				'donate'  => __( 'Faire une donation', 'entrepot' ),
				'history' => __( 'Voir l\'historique', 'entrepot' ),
				'wiki'    => __( 'Lire la documentation', 'entrepot' ),
			);

			if ( ! empty( $repository_data->urls ) ) {
				foreach ( (array) $repository_data->urls as $k_url => $v_url ) {
					// Validate sections.
					if ( ! isset( $allowed_section[ $k_url ] ) ) {
						continue;
					}

					$sections[ $k_url ] = array(
						'text' => $allowed_section[ $k_url ],
						'type' => 'external',
						'url'  => $v_url,
					);

					$is_md = wp_check_filetype( $v_url, array( 'md' => 'text/x-markdown' ) );
					if ( 'md' === $is_md['ext'] ) {
						$sections[ $k_url ]['type'] = 'iframe';
					}
				}
			}

			if ( isset( $repository_data->README ) ) {
				$uri = $repository_data->README;

				array_unshift( $sections, array(
					'text' => __( 'Présentation', 'entrepot' ),
					'type' => 'iframe',
					'url'  => $repository_data->README,
				) );
			}

			if ( $r['section'] && isset( $sections[ $r['section'] ] ) ) {
				$uri = $sections[ $r['section'] ]['url'];
			}

			if ( $uri ) {
				$request  = wp_remote_get( $uri, array(
					'timeout'    => 30,
					'user-agent' => 'Entrepôt/WordPress-Plugin-Updater; ' . get_bloginfo( 'url' ),
				) );

				if ( ! is_wp_error( $request ) && 200 === (int) wp_remote_retrieve_response_code( $request ) ) {
					$repository_info = wp_remote_retrieve_body( $request );
					$parsedown       = new Parsedown();
					$r['text']       = $parsedown->text( $repository_info );
					$r['result']     = 'success';
				}
			}
		}

		wp_enqueue_style( 'entrepot-admin' );
		wp_add_inline_script( 'common', '
			( function( $ ) {
				$( \'#plugin-information-footer .split-button\' ).on( \'click\', \'.split-button-toggle\', function( event ) {
					$( event.delegateTarget ).toggleClass( \'is-open\' );
				} );
			} )( jQuery );
		' );
	}

	iframe_header( strip_tags( $r['title'] ) ); ?>

	<div id="plugin-information-scrollable" class="entrepot">
		<div id="section-holder" class="wrap">

		<?php if ( 'success' === $r['result'] ) :
			/**
			 * Use this filter to add extra formatting.
			 *
			 * @since 1.0.0
			 *
			 * @param string $text   The Content to output. (Repo Information or Upgrade notice).
			 * @param array  $r {
			 *  An array of arguments.
			 *
			 *  @see the entrepot_admin_repository_iframe() function for a complete descripton.
			 * }
			 */
			echo apply_filters( 'entrepot_repository_modal_content', $r['text'], $r );
		else :
			printf( '<div id="message" class="error"><p>%s</p></div>', esc_html( $r['text'] ) );
		endif ; ?>

		</div>
	</div>

	<?php if ( ! empty( $repository_data->releases ) ) :
		$base_url = str_replace( 'releases', '', rtrim( $repository_data->releases, '/' ) );
		$flag_url = entrepot_get_flag_url( $r['repo_name'] );

		if ( ! empty( $repository_data->issues ) ) {
			$sections = array_merge( array(
				'issues' => array(
					'text' => __( 'Rapporter une anomalie', 'entrepot' ),
					'type' => 'external',
					'url'  => $repository_data->issues,
				),
				'pulls' => array(
					'text' => __( 'Contribuer', 'entrepot' ),
					'type' => 'external',
					'url'  => $base_url . 'pulls',
				),
			), $sections );
		}
		?>

		<div id='<?php echo esc_attr( $tab ); ?>-footer'>
			<div class="split-button">
				<div class="split-button-head">
					<a href="<?php echo esc_url( $base_url ); ?>" target="_blank" class="button split-button-primary" aria-live="polite"><?php esc_html_e( 'Voir sur GitHub', 'entrepot' ); ?></a>
					<button type="button" class="split-button-toggle" aria-haspopup="true" aria-expanded="false">
						<i class="dashicons dashicons-arrow-down-alt2"></i>
						<span class="screen-reader-text"><?php esc_html_e( 'Plus d\'actions', 'entrepot' ); ?></span>
					</button>
				</div>
				<ul class="split-button-body">
					<?php foreach( $sections as $k_section => $d_section ) :
						$link   = $d_section['url'];
						$target = ' target="_blank"';

						if ( 'iframe' === $d_section['type'] ) {
							$link   = add_query_arg( 'section', $k_section );
							$target = '';
						}
						?>
						<li>
							<?php printf( '<a href="%1$s"%2$s class="button-link %3$s-button split-button-option">%4$s</a>', esc_url( $link ), $target, esc_attr( $k_section ), esc_html( $d_section['text'] ) ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php if ( 'themes' !== $r['type'] ) : ?>
				<a class="button button-primary entrepot-warning right" href="<?php echo esc_url( $flag_url ); ?>" target="_blank"><?php esc_html_e( 'Signaler', 'entrepot' ); ?></a>
			<?php endif ; ?>
		</div>

	<?php endif;

	iframe_footer();
	exit;
}

/**
 * Gets the changelog section attributes for a plugin or a theme.
 *
 * @since  1.4.0
 *
 * @param  string $slug The repository slug.
 * @param  string $type Whether it's one of the plugins or themes.
 * @return array        The changelog section attributes.
 */
function entrepot_admin_get_changelog_section( $slug, $type = 'plugins' ) {
	$transient_key = 'update_' . $type;
	if ( 'blocks' === $type ) {
		$transient_key = 'entrepot_update_blocks';
	}

	$repository_updates = get_site_transient( $transient_key );
	$args = array();

	if ( empty( $repository_updates->response ) ) {
		return $args;
	}

	$repository = wp_list_filter( $repository_updates->response, array( 'slug' => $slug ) );
	if ( empty( $repository ) || 1 !== count( $repository ) ) {
		return;
	}

	$repository = reset( $repository );

	// Cast the Theme's upgrade data array as an object.
	if ( 'themes' === $type ) {
		$repository = (object) $repository;
	}

	$args['title']   = __( 'Détails de la mise à jour', 'entrepot' );
	$args['context'] = 'repository_upgrade_notice';

	if ( ! empty( $repository->full_upgrade_notice ) ) {
		$upgrade_info = html_entity_decode( $repository->full_upgrade_notice, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$args['text']   = $upgrade_info;
		$args['result'] = 'success';

	} else {
		$args['text'] = __( 'Désolé ce dépôt n\'a pas inclu d\'informations de mise à jour pour cette version.', 'entrepot' );
	}

	return $args;
}

/**
 * Displays the Plugin repository's details.
 *
 * @since 1.4.0
 */
function entrepot_admin_plugin_details() {
	global $tab;

	if ( empty( $_REQUEST['plugin'] ) ) {
		return;
	}

	$section  = '';
	if ( isset( $_REQUEST['section'] ) ) {
		$section = $_REQUEST['section'];
	}

	$plugin     = wp_unslash( $_REQUEST['plugin'] );
	$repository = entrepot_get_repository_json( $plugin, 'plugins' );

	// If it's not an Entrepôt registered repository, leave WordPress handle the plugin.
	if ( ! $repository ) {
		return;
	}

	$args = array(
		'type'       => 'plugins',
		'repo_name'  => $plugin,
		'repository' => $repository,
		'section'    => $section,
	);

	if ( 'changelog' === $section ) {
		$args = array_merge( $args, entrepot_admin_get_changelog_section( $plugin ) );
	}

	return entrepot_admin_repository_iframe( $args );
}

/**
 * Loads a fallback iframe to display a specific Theme details
 * when its preview url is not available.
 *
 * @since 1.4.0
 */
function entrepot_admin_theme_details() {
	if ( ! current_user_can( 'install_themes' ) && ! current_user_can( 'update_themes' ) ) {
		wp_die(
			__( 'Vous n\'êtes pas autorisé à réaliser cette action.', 'entrepot' ),
			__( 'Accès interdit.', 'entrepot' ),
			array( 'response' => 403 )
		);
	}

	if ( empty( $_REQUEST['theme'] ) ) {
		return;
	}

	$section  = '';
	if ( isset( $_REQUEST['section'] ) ) {
		$section = wp_unslash( $_REQUEST['section'] );
	}

	$theme      = wp_unslash( $_REQUEST['theme'] );
	$repository = entrepot_get_repository_json( $theme, 'themes' );

	// If it's not an Entrepôt registered repository, leave WordPress handle the theme.
	if ( ! $repository ) {
		return;
	}

	$args = array(
		'type'       => 'themes',
		'repo_name'  => $theme,
		'repository' => $repository,
		'section'    => $section,
	);

	if ( 'changelog' === $section ) {
		$args = array_merge( $args, entrepot_admin_get_changelog_section( $theme, 'themes' ) );
	}

	return entrepot_admin_repository_iframe( $args );
}

/**
 * Add Entrepôt specific data to Installed Themes list for the registered themes.
 *
 * @since 1.4.0
 *
 * @param  array $prepared_themes The list of JS prepared Themes.
 * @return array                  The list of JS prepared Themes.
 */
function entrepot_prepare_themes_for_js( $prepared_themes = array() ) {
	$current_screen = get_current_screen();

	if ( empty( $current_screen->id ) || 0 !== strpos( 'themes', $current_screen->id ) ) {
		return $prepared_themes;
	}

	foreach( $prepared_themes as &$theme ) {
		$repository = entrepot_get_repository_json( $theme['id'], 'themes' );

		if ( ! $repository ) {
			continue;
		}

		$theme['entrepotData'] = array(
			'entrepot-warning' => array(
				'text' => __( 'Signaler', 'entrepot' ),
				'url'  => entrepot_get_flag_url( $theme['id'] ),
			),
			'entrepot-git' => array(
				'text' => __( 'Voir sur GitHub', 'entrepot' ),
				'url'  => str_replace( 'releases', '', rtrim( $repository->releases, '/' ) ),
			),
		);
	}

	return $prepared_themes;
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
 * Output warnings if the Plugin update requires a newer PHP/WP version comparing to the ones installed.
 *
 * @since 1.5.5
 *
 * @param array $plugin_data An array of the plugin's metadata.
 * @param null|object $response The Plugins's update data.
 */
function entrepot_plugin_row_udpate_info( $plugin_data = array(), $response = null ) {
	include ABSPATH . WPINC . '/version.php'; // include an unmodified $wp_version
	$wp_version = preg_replace( '/-.*$/', '', $wp_version );

	if ( isset( $response->requires_wp ) && $response->requires_wp && version_compare( $wp_version, $response->requires_wp, '<' ) ) {
		printf(
			'<span class="attention entrepot-warning" style="display: block; margin-left: 25px">%s</span>',
			sprintf(
				/* translators: 1: Plugin WordPress required version. 2: WP current version. */
				esc_html__( 'La version de WordPress requise pour cette mise à jour (%1$s) est supérieure à celle qui est installée sur ce site (%2$s).', 'entrepot' ),
				esc_html( $response->requires_wp ),
				esc_html( $wp_version )
			)
		);
	}

	if ( isset( $response->requires_php ) && $response->requires_php && version_compare( phpversion(), $response->requires_php, '<' ) ) {
		printf(
			'<span class="attention entrepot-warning" style="display: block; margin-left: 25px">%s</span>',
			sprintf(
				/* translators: 1: Plugin PHP required version. 2: PHP current version. */
				esc_html__( 'La version de PHP requise pour cette mise à jour (%1$s) est supérieure à celle qui est installée sur ce site (%2$s).', 'entrepot' ),
				esc_html( $response->requires_wp ),
				esc_html( $wp_version )
			)
		);
	}
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

	if ( isset( $plugin_data['is_update'] ) && true === $plugin_data['is_update'] && isset( $plugin_data['full_upgrade_notice'] ) ) {
		add_action( "in_plugin_update_message-{$plugin_file}", 'entrepot_plugin_row_udpate_info', 10, 2 );
	}

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

	$notices        = array();
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
		'site_admin_notice',
		'WP_Privacy_Policy_Content::policy_text_changed_notice',
	), 0 );

	ob_start();

	foreach ( $registered_notices as $hook_key => $priorities ) {
		foreach ( $priorities->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $hook_name => $hook_data ) {
				if ( isset( $core_hooks[ $hook_name ] ) ) {
					continue;
				}

				// Trigger the callback without altering the hooks count.
				call_user_func_array( $hook_data['function'], array() );

				$n_key = $hook_name;
				/**
				 * To allow all notices to be trashed class methods need
				 * to use a class::method string as the idx for the hook_name
				 * is different at every page load.
				 */
				if ( is_array( $hook_data['function'] ) ) {
					if ( is_object( $hook_data['function'][0] ) ) {
						$class = get_class( $hook_data['function'][0] );
					} else {
						$class = $hook_data['function'][0];
					}

					$n_key = $class . '::' . $hook_data['function'][1];
				}

				$notices[ $n_key ] = ob_get_contents();
				ob_clean();

				// Remove the action on the core hook
				remove_action( $hook_key, $hook_name, $priority );
			}
		}
	}

	/**
	 * @todo  deprecate
	 */
	do_action( 'entrepot_notices' );

	ob_end_clean();
	$entrepot_notices = array_fill_keys( array( 'upgrade', 'error', 'updated' ), array() );

	// Counts
	$all_notices_count     = 0;
	$upgrade_notices_count = 0;
	$updated_notices_count = 0;
	$error_notices_count   = 0;
	$info_notices_count    = 0;

	if ( ! empty( $notices ) ) {
		$notices      = array_filter( $notices );

		foreach ( $notices as $notice_key => $notice_text ) {
			preg_match( '/\s*<div.*class=\"(.*?)\"[.*|>]\s*/', $notice_text, $matches );

			if ( empty( $matches[1] ) ) {
				continue;
			}

			$classes = explode( ' ', $matches[1] );

			$notice_object = (object) array(
				'id'         => $notice_key,
				'short_text' => sprintf( '<p id="%1$s">%2$s</p>', $notice_key, wp_trim_words( $notice_text, 20, null ) ),
				'full_text'  => $notice_text,
			);

			if ( in_array( 'update-nag', $classes, true ) ) {
				$entrepot_notices['upgrade'][] = $notice_object;
				$upgrade_notices_count += 1;
			} else if ( in_array( 'error', $classes, true ) ) {
				$entrepot_notices['error'][] = $notice_object;
				$error_notices_count += 1;
			}  else if ( in_array( 'updated', $classes, true ) ) {
				$entrepot_notices['updated'][] = $notice_object;
				$updated_notices_count += 1;
			} else {
				$entrepot_notices['info'][] = $notice_object;
				$info_notices_count += 1;
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
				'info' => sprintf(
					'<span class="count">%1$d</span> <span class="text">%2$s</span>',
					$info_notices_count,
					_nx( 'Info', 'Infos', $info_notices_count, 'Admin Notices Center tab', 'entrepot' )
				),
				'updated' => sprintf(
					'<span class="count">%1$d</span> <span class="text">%2$s</span>',
					$updated_notices_count,
					_nx( 'Succès', 'Succès', $updated_notices_count, 'Admin Notices Center tab', 'entrepot' )
				),
				'error'   => sprintf(
					'<span class="count">%1$d</span> <span class="text">%2$s</span>',
					$error_notices_count,
					_nx( 'Erreur', 'Erreurs', $error_notices_count, 'Admin Notices Center tab', 'entrepot' )
				),
			),
			'trash' => __( 'Ne plus afficher cette alerte', 'entrepot' ),
			'show'  => __( 'Ouvrir la notice.', 'entrepot' ),
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

/**
 * Overrides the Plugins list when displaying the Plugin Editor Administration screen.
 *
 * @since 1.2.0
 */
function entrepot_admin_plugin_editor_load() {
	$entrepot = entrepot();

	// Init plugins cache.
	get_plugins();

	// Catch the plugins cache to eventually reset it.
	$entrepot->plugins_cache = wp_cache_get( 'plugins', 'plugins' );

	if ( ! empty( $entrepot->plugins_cache[''] ) ) {
		$entrepot_plugins_cache = array( '' => array() );

		foreach ( $entrepot->plugins_cache[''] as $key_plugin => $plugin_data ) {
			if ( true === (bool) $plugin_data['Allow File Edits'] || 'hello.php' === $key_plugin ) {
				$entrepot_plugins_cache[''][ $key_plugin ] = $plugin_data;
			}
		}

		// Look for custom function files allowing edits.
		$plugins_dir = @ opendir( WP_PLUGIN_DIR );

		if ( $plugins_dir ) {
			while ( ($file = readdir( $plugins_dir ) ) !== false ) {
				if ( '.' === substr( $file, 0, 1 ) || is_dir( WP_PLUGIN_DIR . '/' . $file ) ) {
					continue;
				}

				if ( '.php' === substr( $file, -4 ) && ! isset( $entrepot->plugins_cache[''][ $file ] ) && 'index.php' !== $file ) {
					$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $file, false, false );

					if ( true === (bool) $plugin_data['Allow File Edits'] ) {
						if ( empty( $plugin_data['Name'] ) ) {
							$plugin_data['Name'] = wp_basename( $file, '.php' );
						}

						$entrepot_plugins_cache[''][ plugin_basename( $file ) ] = $plugin_data;
					}
				}
			}

			closedir( $plugins_dir );
		}

		/**
		 * Use this restricted list of plugins/custom functions files
		 * instead of regular plugins.
		 */
		wp_cache_set( 'plugins', $entrepot_plugins_cache, 'plugins' );
	}
}

/**
 * Use the restricted list of plugins when saving Plugin edits
 *
 * @since 1.2.0
 */
function entrepot_ajax_before_edit_plugin_file() {
	if ( ! isset( $_POST['plugin'] ) ) {
		return;
	}

	entrepot_admin_plugin_editor_load();
}

/**
 * Resets the regular Plugins list.
 *
 * @since 1.2.0
 */
function entrepot_admin_plugin_editor_footer() {
	$entrepot = entrepot();

	// Use the catched plugins cach to restore the plugins cache.
	wp_cache_set( 'plugins', $entrepot->plugins_cache, 'plugins' );
}

/**
 * Resets the regular Plugins list after Plugin edits have been saved.
 *
 * @since 1.2.0
 */
function entrepot_ajax_after_edit_plugin_file() {
	if ( ! isset( $_POST['plugin'] ) ) {
		return;
	}

	entrepot_admin_plugin_editor_footer();
}

/**
 * Output warnings if the Plugin update requires a newer PHP/WP version comparing to the ones installed.
 *
 * @since 1.5.5
 *
 * @param boolean $is_entrepot True if the check happens into the Entrpôt context.
 *                             False otherwise. Default `false`
 * @return void|array Nothing or the requirements for Entrepôt Plugin repositories.
 */
function entrepot_admin_check_plugin_requires( $is_entrepot = false ) {
	include ABSPATH . WPINC . '/version.php'; // include an unmodified $wp_version
	$wp_version = preg_replace( '/-.*$/', '', $wp_version );

	$plugins     = get_site_transient( 'update_plugins' );
	$script_data = array();

	if ( isset( $plugins->response ) && is_array( $plugins->response ) ) {
		foreach ( $plugins->response as $plugin_file => $plugin_data ) {
			if ( ! $is_entrepot && ! entrepot_get_repository_json( $plugin_data->slug ) ) {
				continue;
			}

			$script_data[ $plugin_file ] = array();
			$requires_php                = '';
			$requires_wp                 = '';
			$icon                        = '';

			if ( isset( $plugin_data->requires_php ) && version_compare( phpversion(), $plugin_data->requires_php, '<' ) ) {
				$requires_php = $plugin_data->requires_php;
			}

			if ( isset( $plugin_data->requires_wp ) && version_compare( $wp_version, $plugin_data->requires_wp, '<' ) ) {
				$requires_wp = $plugin_data->requires_wp;
			}

			if ( isset( $plugin_data->icons['1x'] ) ) {
				$icon = $plugin_data->icons['1x'];
			}

			if ( ( '' !== $icon || true === $is_entrepot ) && ( $requires_php || $requires_wp ) ) {
				$script_data[ $plugin_file ] = array(
					'icon' => $icon,
					'php'  => $requires_php,
					'wp'   => $requires_wp,
				);
			}
		}
	}

	// Set warnings.
	$warnings = array(
		'WP'  => sprintf(
			/* translators: %s is the current WordPress version. wpVersion is to leave as is */
			_x( 'La version de WordPress requise pour cette mise à jour (wpVersion) est supérieure à celle qui est installée sur ce site (%s).', 'JavaScript string', 'entrepot' ),
			esc_html( $wp_version )
		),
		'PHP' => sprintf(
			/* translators: %s is the current PHP version. wpVersion is to leave as is */
			_x( 'La version de PHP requise pour cette mise à jour (phpVersion) est supérieure à celle qui est installée sur ce site (%s).', 'JavaScript string', 'entrepot' ),
			esc_html( phpversion() )
		),
	);

	if ( true === $is_entrepot ) {
		return array( 'data' => $script_data, 'warnings' => $warnings );
	}

	$script_data = array_values( $script_data );

	if ( ! array_filter( $script_data ) ) {
		return;
	}

	wp_enqueue_script( 'entrepot-updates' );
	wp_localize_script(
		'entrepot-updates',
		'entrepotRepositories',
		array(
			'plugins'  => $script_data,
			'warnings' => $warnings,
		)
	);
}
