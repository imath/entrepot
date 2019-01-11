<?php
/**
 * Entrepôt Blocks functions.
 *
 * @package Entrepôt\inc
 *
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gets the full path to the standalone blocks repository.
 *
 * @since 1.5.0
 *
 * @return string Full path to the standalone blocks repository.
 */
function entrepot_blocks_dir() {
	$blocks_dir = trailingslashit( WP_CONTENT_DIR ) . 'blocks';

	/**
	 * Use this filter to use another repository for standalone blocks.
	 *
	 * @since 1.5.0
	 *
	 * @param  string  $blocks_dir The repository name of standalone blocks.
	 */
	return apply_filters( 'entrepot_installed_blocks_dir', $blocks_dir );
}

/**
 * Gets the root url for standalone blocks.
 *
 * @since 1.5.0
 *
 * @return string The root url standalone blocks.
 */
function entrepot_blocks_url() {
	$blocks_url = trailingslashit( content_url( 'blocks' ) );

	/**
	 * Use this filter to use another url for standalone blocks.
	 *
	 * @since 1.5.0
	 *
	 * @param  string  $blocks_url The root url standalone blocks.
	 */
	return apply_filters( 'entrepot_blocks_url', $blocks_url );
}

/**
 * Map custom caps to existing WordPress caps.
 *
 * @since 1.5.0
 *
 * @param  array   $caps    The user's actual capabilities.
 * @param  string  $cap     The requested Capability name.
 * @param  integer $user_id The user ID.
 * @param  array   $args    The cap's context.
 * @return array   $caps    The user's mapped capabilities.
 */
function entrepot_blocks_map_custom_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
	if ( 'activate_entrepot_blocks' === $cap ) {
		$caps = array( 'activate_plugins' );
	} elseif ( 'install_entrepot_blocks' === $cap ) {
		$caps = array( 'install_plugins' );
	} elseif ( 'update_entrepot_blocks' === $cap ) {
		$caps = array( 'update_plugins' );
	} elseif ( 'entrepot_delete_blocks' === $cap ) {
		$caps = array( 'delete_plugins' );
	} elseif( 'entrepot_manage_network_blocks' === $cap ) {
		$caps = array( 'manage_network_plugins' );
	}

	return $caps;
}

/**
 * Set a block's data from its block.json file.
 *
 * @since 1.5.0
 *
 * @param  array  $blocks The list of installed blocks.
 * @param  string $json   The absolute path to the block.json file.
 * @return array          The list of installed blocks.
 */
function entrepot_set_block_data( $blocks = array(), $json ) {
	if ( is_readable( $json ) ) {
		$json_data        = file_get_contents( $json );
		$block_data       = json_decode( $json_data );
		$block_data->path = dirname( $json );
		$block_dir        = wp_basename( $block_data->path );

		if ( ! isset( $block_data->github_url ) ) {
			return $blocks;
		}

		$url_parts = wp_parse_url( $block_data->github_url );
		if ( 'github.com' !== $url_parts['host'] || ! $url_parts['path'] ) {
			return $blocks;
		}

		$path_parts = array_filter( explode( '/', $url_parts['path'] ) );
		$elements   = count( $path_parts );
		if ( ! $path_parts || $elements < 2 ) {
			return $blocks;
		}

		$block_data->id = $path_parts[ $elements - 1 ] . '/' . rtrim( $path_parts[ $elements ], '.git' );

		if ( ! isset( $blocks[ $block_dir ] ) ) {
			$blocks[ $block_dir ] = $block_data;
		}
	}

	return $blocks;
}

/**
 * Clears the Blocks cache used by entrepot_get_blocks() and by default, the Blocks Update cache.
 *
 * @since 1.5.0
 *
 * @param bool $clear_update_cache Whether to clear the Block updates cache.
 */
function entrepot_blocks_clear_cache( $clear_update_cache = true ) {
	if ( $clear_update_cache ) {
		delete_site_transient( 'entrepot_update_blocks' );
	}

	wp_cache_delete( 'blocks', 'entrepot' );
}

/**
 * Get data for all blocks or for a specific block.
 *
 * @since 1.5.0
 *
 * @param  string       $block_dir The name of the block directory. Optional.
 * @return array|object            The list of blocks or a specific one.
 */
function entrepot_get_blocks( $block_dir = '' ) {
	$blocks_cache = wp_cache_get( 'blocks', 'entrepot' );

	if ( ! $blocks_cache ) {
		$blocks_cache = array();
	}

	if ( count( $blocks_cache ) > 1 ) {
		if ( $block_dir && isset( $blocks_cache[ $block_dir ] ) ) {
			return $blocks_cache[ $block_dir ];
		}

		return $blocks_cache;
	}

	$entrepot_blocks = array();
	$blocks_root     = entrepot_blocks_dir();

	if ( $block_dir ) {
		$blocks_root = trailingslashit( $blocks_root ) . trim( $block_dir, '/' );
	}

	// Files in wp-content/blocks directory
	$blocks_dir = @ opendir( $blocks_root );

	if ( $blocks_dir ) {
		while ( ( $file = readdir( $blocks_dir ) ) !== false ) {
			if ( 'block.json' === $file && $block_dir ) {
				$entrepot_blocks = entrepot_set_block_data(
					$entrepot_blocks,
					$blocks_root . '/' . $file
				);
			} elseif ( is_dir( $blocks_root . '/' . $file ) && '.' !== substr( $file, 0, 1 ) ) {
				$blocks_subdir = @ opendir( $blocks_root . '/' . $file );

				if ( $blocks_subdir ) {
					while ( ( $subfile = readdir( $blocks_subdir ) ) !== false ) {
						if ( 'block.json' !== $subfile ) {
							continue;
						}

						$entrepot_blocks = entrepot_set_block_data(
							$entrepot_blocks,
							$blocks_root . '/' . $file . '/' . $subfile
						);
					}

					closedir( $blocks_subdir );
				}
			}
		}

		closedir( $blocks_dir );
	}

	if ( ! $entrepot_blocks ) {
		return array();
	}

	ksort( $entrepot_blocks );
	wp_cache_set( 'blocks', $entrepot_blocks, 'entrepot' );

	if ( $block_dir && isset( $entrepot_blocks[ $block_dir ] ) ) {
		return $entrepot_blocks[ $block_dir ];
	}

	return $entrepot_blocks;
}

/**
 * Loads the PHP part of blocks registered into the Entrepôt.
 *
 * @since 1.5.0
 */
function entrepot_block_types_loaded() {
	$blocks        = entrepot_get_blocks();
	$active_blocks = (array) get_site_option( 'entrepot_active_blocks', array() );

	foreach ( $blocks as $block ) {
		if ( ! in_array( $block->id, $active_blocks, true ) || ! isset( $block->php_relative_path ) ) {
			continue;
		}

		$php_loader = trailingslashit( $block->path ) . $block->php_relative_path;

		if ( file_exists( $php_loader ) ) {
			require_once $php_loader;
		}
	}

	/**
	 * Add custom code once the PHP parts of blocks is loaded.
	 *
	 * @since 1.5.0
	 */
	do_action( 'entrepot_block_types_loaded' );
}

/**
 * Register "Entrepôt" block types into the Block Editor.
 *
 * @since 1.5.0
 */
function entrepot_register_block_types() {
	$blocks        = entrepot_get_blocks();
	$active_blocks = (array) get_site_option( 'entrepot_active_blocks', array() );
	$blocks_url    = trailingslashit( entrepot_blocks_url() );

	foreach ( $blocks as $block_dir => $block ) {
		$block_args    = array();
		$block_version = time();

		if ( ! in_array( $block->id, $active_blocks, true ) || ! isset( $block->block_id ) ) {
			continue;
		}

		if ( isset( $block->version ) && $block->version ) {
			$block_version = $block->version;
		}

		if ( isset( $block->editor_script ) && is_object( $block->editor_script ) ) {
			$script_data = wp_parse_args( (array) $block->editor_script, array(
				'handle'        => '',
				'relative_path' => '',
				'dependencies'  => array(),
			) );

			if ( ! $script_data['handle'] || ! $script_data['relative_path'] || ! file_exists( trailingslashit( $block->path ) . $script_data['relative_path'] ) ) {
				continue;
			}

			$script_data['url'] = trailingslashit( $blocks_url . $block_dir ) . ltrim( $script_data['relative_path'], '/' );
			$block_args['editor_script'] = sanitize_key( $script_data['handle'] );

			wp_register_script(
				$block_args['editor_script'],
				esc_url_raw( $script_data['url'] ),
				(array) $script_data['dependencies'],
				esc_attr( $block_version ),
				true
			);
		}

		if ( isset( $block->render_callback ) && $block->render_callback && function_exists( $block->render_callback ) ) {
			$block_args['render_callback'] = $block->render_callback;
		}

		if ( isset( $block->attributes ) && is_object( $block->attributes ) ) {
			$attributes = array();

			foreach ( $block->attributes as $key_attribute => $attribute ) {
				$attributes[ $key_attribute ] = (array) $attribute;
			}

			if ( $attributes ) {
				$block_args['attributes'] = $attributes;
			}
		}

		foreach ( array( 'editor_style', 'style' ) as $style ) {
			if ( isset( $block->{$style} ) && is_object( $block->{$style} ) ) {
				$style_data = wp_parse_args( (array) $block->{$style}, array(
					'handle'        => '',
					'relative_path' => '',
					'dependencies'  => array(),
				) );

				if ( ! $style_data['handle'] || ! $style_data['relative_path'] || ! file_exists( trailingslashit( $block->path ) . $style_data['relative_path'] ) ) {
					continue;
				}

				$style_data['url'] = trailingslashit( $blocks_url . $block_dir ) . ltrim( $style_data['relative_path'], '/' );
				$block_args[ $style ] = sanitize_key( $style_data['handle'] );

				wp_register_style(
					$block_args[ $style ],
					esc_url_raw( $style_data['url'] ),
					(array) $style_data['dependencies'],
					esc_attr( $block_version )
				);
			}
		}

		if ( $block_args ) {
			register_block_type( $block->block_id, $block_args );
		}
	}

	/**
	 * Add custom actions once Block Types from the "Entrepôt" are registered.
	 *
	 * @since 1.5.0
	 */
	do_action( 'entrepot_registered_blocks' );
}

/**
 * Add an admin menu to manage Block Types.
 *
 * @since 1.5.0
 */
function entrepot_blocks_admin_menu() {
	/* Translators: %s is the Update notice bubble html */
	$menu_title = __( 'Types de Bloc%s', 'entrepot' );
	$notice     = '';

	$block_updates = get_site_transient( 'entrepot_update_blocks' );
	if ( isset( $block_updates->response ) && $block_updates->response ) {
		$count  = count( $block_updates->response );
		$notice = sprintf( ' <span class="update-plugins count-%1$s">
			<span class="update-count">%2$s</span>
		</span>', $count, number_format_i18n( $count ) );
	}

	$screen = add_menu_page(
		__( 'Gestion des types de bloc', 'entrepot' ),
		sprintf( $menu_title, $notice ),
		'activate_entrepot_blocks',
		'entrepot-blocks',
		'entrepot_admin_blocks',
		'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPHN2ZyB2ZXJzaW9uPSIxLjEiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMTAwIDEwMCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMTAwIDEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8Zz4KICAgIDxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNODAsNzBIMjBWMzNoOXYtN2gxNnY3aDEydi03aDE2djdoN1Y3MHoiIHN0eWxlPSJmaWxsOiByZ2IoMjU1LCAyNTUsIDI1NSk7Ii8+CiAgPC9nPgo8L3N2Zz4=',
		'network_admin_menu' !== current_action() ? 67 : 24
	);

	add_action( 'load-' . $screen, 'entrepot_admin_blocks_load' );
}

/**
 * Add an admin bar menu to manage Block Types.
 *
 * @since 1.5.0
 *
 * @param WP_Admin_Bar $wp_admin_bar The admin bar.
 */
function entrepot_blocks_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {
	if ( current_user_can( 'entrepot_manage_network_blocks' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'parent' => 'network-admin',
				'id'     => 'network-admin-blocks',
				'title'  => __ ( 'Types de Bloc', 'entrepot' ),
				'href'   => esc_url( add_query_arg( 'page', 'entrepot-blocks', network_admin_url( 'admin.php' ) ) ),
			)
		);
	}
}

/**
 * Register Script and translation for the Block Admin.
 *
 * @since 1.5.0
 */
function entrepot_admin_blocks_register_scripts() {
	wp_register_script(
		'entrepot-manage-blocks',
		sprintf( '%1$sdist/index%2$s.js', entrepot_root_url(), entrepot_min_suffix() ),
		array( 'wp-element', 'wp-i18n', 'wp-api-fetch', 'lodash', 'plugin-install' ),
		entrepot_version(),
		true
	);

	wp_set_script_translations( 'entrepot-manage-blocks', 'entrepot', trailingslashit( entrepot_root_path() ) . 'languages/js' );
}

/**
 * Display more information about the Block.
 *
 * @since 1.5.0
 *
 * @param string $block_name The name of the Block repository.
 */
function entrepot_admin_block_detail( $block_name ) {
	define( 'IFRAME_REQUEST', true );
	wp_add_inline_style( 'common', 'body { background: #FFF }' );

	$repository = entrepot_get_repository_json( $block_name, 'blocks' );
	if ( ! $repository ) {
		wp_die( __( 'Ce bloc n’est pas enregistré dans l’Entrepôt, désolé.', 'entrepot' ) );
	}

	$section  = '';
	if ( isset( $_REQUEST['section'] ) ) {
		$section = wp_unslash( $_REQUEST['section'] );
	}

	$args = array(
		'type'       => 'blocks',
		'repo_name'  => $block_name,
		'repository' => $repository,
		'section'    => $section,
	);

	if ( 'changelog' === $section ) {
		$args = array_merge( $args, entrepot_admin_get_changelog_section( $block_name, 'blocks' ) );
	}

	return entrepot_admin_repository_iframe( $args );
}

/**
 * Used to check PHP errors while activating blocks.
 *
 * @since 1.5.0
 *
 * @param object $block A block type object.
 */
function entrepot_block_sandbox( $block = null ) {
	if ( ! isset( $block->path ) || ! isset( $block->php_relative_path ) ) {
		return false;
	}

	$loader = trailingslashit( $block->path ) . $block->php_relative_path;

	if ( file_exists( $loader ) ) {
		include_once trailingslashit( $block->path ) . $block->php_relative_path;

		/**
		 * Add custom code once the PHP parts of blocks is loaded.
		 *
		 * @since 1.5.0
		 */
		do_action( 'entrepot_block_types_loaded' );
	}
}

/**
 * Activate a block type making sure no PHP errors are triggered.
 *
 * @since 1.5.0
 *
 * @param string $block_type_id The block type ID.
 * @param string $redirect The url to redirect the user to.
 * @return WP_Error|boolean True if the block type was activated. An error object otherwise.
 */
function entrepot_activate_block( $block_type_id = '', $redirect = '' ) {
	$active_blocks = (array) get_site_option( 'entrepot_active_blocks', array() );
	$block_dir     = wp_basename( $block_type_id );
	$block         = entrepot_get_blocks( $block_dir );

	if ( ! isset( $block->id ) || ! isset( $block->path ) || $block_type_id !== $block->id ) {
		return new WP_Error( 'entrepot_blocks_not_installed', __( 'Le type de bloc n’est pas installé.', 'entrepot' ), array( 'status' => 404 ) );
	}

	if ( in_array( $block->id, $active_blocks, true ) ) {
		return new WP_Error( 'entrepot_blocks_allready_installed', __( 'Le type de bloc est déjà activé.', 'entrepot' ), array( 'status' => 403 ) );
	}

	if ( $redirect ) {
		wp_redirect( add_query_arg( '_error_nonce', wp_create_nonce( 'block-activation-error_' . $block->id ), $redirect ) );
	}

	ob_start();
	entrepot_block_sandbox( $block );

	if ( ob_get_length() > 0 ) {
		$output = ob_get_clean();
		return new WP_Error( 'entrepot_blocks_unexpected_output', __( 'Le bloc a généré un affichage inattendu.', 'entrepot' ), $output );
	}

	ob_end_clean();

	$active_blocks[] = $block->id;
	update_site_option( 'entrepot_active_blocks', array_unique( $active_blocks ) );

	return true;
}

/**
 * Deactivate a block.
 *
 * @since 1.5.0
 *
 * @param string $block_type_id The block type ID.
 * @return boolean True.
 */
function entrepot_deactivate_block( $block_type_id = '' ) {
	$active_blocks = (array) get_site_option( 'entrepot_active_blocks', array() );
	$block_index   = array_search( $block_type_id, $active_blocks, true );

	if ( false !== $block_index ) {
		unset( $active_blocks[ $block_index ] );
		update_site_option( 'entrepot_active_blocks', array_unique( $active_blocks ) );
	}

	return true;
}

/**
 * Outputs a form to request Filesystem credentials.
 *
 * @since 1.5.0
 *
 * @param string $data The HTML form output.
 */
function entrepot_block_request_credentials_form( $data = '' ) {
	require_once ABSPATH . 'wp-admin/admin-header.php';
	echo $data;
	require ABSPATH . 'wp-admin/admin-footer.php';
}

/**
 * Delete a block.
 *
 * @since 1.5.0
 *
 * @param string $block_type_id The block type ID.
 * @return WP_Error|boolean True if the block type was deleted. An error object otherwise.
 */
function entrepot_delete_block( $block_type_id = '' ) {
	global $wp_filesystem;

	// Get the block.
	$block_dir = wp_basename( $block_type_id );
	$block     = entrepot_get_blocks( $block_dir );

	if ( ! isset( $block->name ) ) {
		return new WP_Error( 'entrepot_delete_unknown_block', __( 'Le bloc n’a pas été trouvé. Il ne semble pas être installé.', 'entrepot' ) );
	}

	$active_blocks = (array) get_site_option( 'entrepot_active_blocks', array() );
	$block_index   = array_search( $block_type_id, $active_blocks, true );

	if ( false !== $block_index ) {
		return new WP_Error( 'entrepot_delete_active_block', __( 'Le bloc est activé. Merci de le désactiver avant de le supprimer.', 'entrepot' ) );
	}

	$url = wp_nonce_url( add_query_arg( array(
		'page'     => 'entrepot-blocks',
		'action'   => 'delete',
		'block'    => $block_type_id,
	), network_admin_url( 'admin.php' ) ), 'delete-block_' . $block_type_id );

	ob_start();
	$credentials = request_filesystem_credentials( $url );
	$data = ob_get_clean();

	if ( false === $credentials ) {
		if ( ! empty( $data ) ){
			entrepot_block_request_credentials_form( $data );
			exit;
		}
		return;
	}

	if ( ! WP_Filesystem( $credentials ) ) {
		ob_start();
		request_filesystem_credentials( $url, '', true );
		$data = ob_get_clean();

		if ( ! empty( $data ) ){
			entrepot_block_request_credentials_form( $data );
			exit;
		}
		return;
	}

	if ( ! is_object( $wp_filesystem ) ) {
		return new WP_Error( 'fs_unavailable', __( 'Impossible de se connecter au système de fichiers.', 'entrepot' ) );
	}

	if ( is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
		return new WP_Error( 'fs_error', __( 'Erreur du système de fichiers.', 'entrepot' ), $wp_filesystem->errors );
	}

	// Get the base Blocks folder.
	$blocks_dir = $wp_filesystem->find_folder( entrepot_blocks_dir() );

	if ( empty( $blocks_dir ) ) {
		return new WP_Error( 'fs_no_entrepot_blocks_dir', __( 'Le dossier des blocs de l’Entrepôt n’a pu être localisé.', 'entrepot' ) );
	}

	$block_dir = trailingslashit( $blocks_dir ) . $block_dir;
	$deleted   = $wp_filesystem->delete( $block_dir, true );

	// Remove the deleted block from the Blocks to upgrade.
	$block_updates = get_site_transient( 'entrepot_update_blocks' );
	if ( isset( $block_updates->response[ $block_type_id ] ) ) {
		unset( $block_updates->response[ $block_type_id ] );
		set_site_transient( 'entrepot_update_blocks', $block_updates );
	}

	if ( ! $deleted ) {
		return new WP_Error( 'could_not_remove_block', __( 'Une erreur inconnue est survenue lors de la suppression du bloc.', 'entrepot' ) );
	}

	return true;
}

/**
 * Get Entrepôt registered blocks action results' feedback.
 *
 * @since 1.5.0
 *
 * @return string HTML Output.
 */
function entrepot_admin_get_feedback_messages() {
	$feedback = '';

	if ( isset( $_GET['error'] ) && isset( $_GET['block'] ) ) {
		$block_id = wp_unslash( $_GET['block'] );
		$is_dismissible = '';

		if ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
			$is_dismissible = ' is-dismissible';
		}

		if ( isset( $_GET['charsout'] ) ) {
			$feedback = sprintf( '<div id="message" class="error notice%1$s"><p>%2$s</p></div>',
				$is_dismissible,
				sprintf(
					esc_html__( 'Le bloc a généré un affichage inattendu de %d caractère(s) et n’a pas été activé.', 'entrepot' ),
					$_GET['charsout']
				)
			);
		} elseif ( wp_verify_nonce( $_GET['_error_nonce'], 'block-activation-error_' . $block_id ) ) {
			$iframe_url = add_query_arg( array(
				'page'     => 'entrepot-blocks',
				'action'   => 'error_scrape',
				'block'    => $block_id,
				'_wpnonce' => $_GET['_error_nonce'],
			), network_admin_url( 'admin.php' ) );
			$feedback = sprintf( '<div id="message" class="error notice%1$s"><p>%2$s</p><iframe style="border:0" width="%3$s" height="70px" src="%4$s"></iframe></div>',
				$is_dismissible,
				esc_html__( 'Le bloc a généré une erreur fatale et n’a pas été activé.', 'entrepot' ),
				'100%',
				esc_url_raw( $iframe_url )
			);
		}
	} elseif ( isset( $_GET['updated'] ) ) {
		if ( isset( $_GET['enabled'] ) && $_GET['enabled'] ) {
			$feedback = sprintf( '<div id="message" class="updated notice%1$s"><p>%2$s</p></div>',
				$is_dismissible,
				esc_html__( 'Le bloc a été activé avec succès.', 'entrepot' )
			);
		} elseif ( isset( $_GET['disabled'] ) && $_GET['disabled'] ) {
			$feedback = sprintf( '<div id="message" class="updated notice%1$s"><p>%2$s</p></div>',
				$is_dismissible,
				esc_html__( 'Le bloc a été désactivé avec succès.', 'entrepot' )
			);
		}
	} elseif ( isset( $_GET['deleted'] ) ) {
		$feedback = sprintf( '<div id="message" class="updated notice%1$s"><p>%2$s</p></div>',
			$is_dismissible,
			esc_html__( 'Le bloc a été supprimé avec succès.', 'entrepot' )
		);
	}

	return $feedback;
}

/**
 * Handle Admin screen actions.
 *
 * @since 1.5.0
 */
function entrepot_admin_blocks_load() {
	$in_iframe = false;
	$redirect  = add_query_arg( array(
		'page'     => 'entrepot-blocks',
	), network_admin_url( 'admin.php' ) );

	if ( isset( $_GET['updating-block'] ) ) {
		$in_iframe = true;
		$redirect  = add_query_arg( 'updating-block', 1, $redirect );
	}

	if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['block'] ) ) {
		$block_id = wp_unslash( $_REQUEST['block'] );
		$action   = wp_unslash( $_REQUEST['action'] );

		if ( ! current_user_can( 'activate_entrepot_blocks' ) && in_array( $action, array( 'activate', 'error_scrape', 'deactivate' ), true ) ) {
			wp_die( __( 'Désolé, vous n’êtes pas autorisé·e à activer ou à désactiver des blocs.', 'entrepot' ) );
		}

		// Block information.
		if ( 'block-information' === $action ) {
			entrepot_admin_block_detail( wp_basename( $block_id ) );

		// Activate a block.
		} elseif ( 'activate' === $action ) {
			check_admin_referer( "$action-block_$block_id" );

			$activated = entrepot_activate_block( $block_id, add_query_arg( array(
				'error'    => true,
				'block'    => $block_id,
			), $redirect ) );

			if ( is_wp_error( $activated ) ) {
				if ( 'entrepot_blocks_unexpected_output' === $activated->get_error_code() ) {
					wp_redirect( add_query_arg( array(
						'error'        => true,
						'charsout'     => strlen( $activated->get_error_data() ),
						'block'        => $block_id,
						'_error_nonce' => wp_create_nonce( 'block-activation-error_' . $block_id )
					), $redirect ) );
					exit;
				} else {
					wp_die( $activated );
				}
			}

			$redirect = add_query_arg( array(
				'updated' => true,
				'enabled' => $block_id,
			), $redirect );

		// Display the error into an iframe.
		} elseif ( 'error_scrape' === $action ) {
			check_admin_referer( 'block-activation-error_' . $block_id );

			if ( ! WP_DEBUG ) {
				error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
			}

			@ini_set( 'display_errors', true );

			// Get the block.
			$block_dir = wp_basename( $block_id );
			$block     = entrepot_get_blocks( $block_dir );

			// Go back to "sandbox" to get the same errors as before.
			entrepot_block_sandbox( $block );
			exit;

		// Deactivate a block.
		} elseif ( 'deactivate' === $action ) {
			check_admin_referer( "$action-block_$block_id" );

			entrepot_deactivate_block( $block_id );

			$redirect = add_query_arg( array(
				'updated' => true,
				'disabled' => $block_id,
			), $redirect );

		// Install a block.
		} elseif ( 'install' === $action ) {
			if ( ! current_user_can( 'install_entrepot_blocks' ) ) {
				wp_die( __( 'Désolé, vous n’êtes pas autorisé·e à installer des blocs.', 'entrepot' ) );
			}

			check_admin_referer( "$action-block_$block_id" );

			require_once ABSPATH . 'wp-admin/admin-header.php';

			$args = (object) array( 'slug' => wp_basename( $block_id ) );
			$api  = entrepot_repositories_api( false, 'block_information', $args );

			if ( ! $api ) {
				wp_die( __( 'Désolé, le bloc à installer n’est pas enregistré dans l’Entrepôt.', 'entrepot' ) );
			}

			$upgrader = new Entrepot_Block_Upgrader( new Entrepot_Block_Installer_Skin( array(
				'title' => sprintf( __( 'Installation du bloc : %s', 'entrepot' ), $api->name . ' ' . $api->version ),
				'url'   => add_query_arg( array(
					'page'     => 'entrepot-blocks',
					'action'   => 'install',
					'block'    => $block_id,
				), network_admin_url( 'admin.php' ) ),
				'nonce' => 'install-block_' . $block_id,
				'block' => $block_id,
				'api'   => $api,
			) ) );
			$upgrader->install( $api->download_link, array( 'block' => $block_id ) );

			require ABSPATH . 'wp-admin/admin-footer.php';
			exit();

		// Update a block.
		} elseif ( 'update' == $action ) {
			if ( ! current_user_can( 'update_entrepot_blocks' ) ) {
				wp_die( __( 'Désolé, vous n’êtes pas autorisé·e à mettre à jour des blocs.', 'entrepot' ) );
			}

			check_admin_referer( "$action-block_$block_id" );

			require_once ABSPATH . 'wp-admin/admin-header.php';

			$args = (object) array( 'slug' => wp_basename( $block_id ) );
			$api  = entrepot_repositories_api( false, 'block_information', $args );

			if ( ! $api ) {
				wp_die( __( 'Désolé, le bloc à mettre à jour n’est pas enregistré dans l’Entrepôt.', 'entrepot' ) );
			}

			$upgrader = new Entrepot_Block_Upgrader( new Entrepot_Block_Upgrader_Skin( array(
				'title' => sprintf( __( 'Mise à jour du bloc : %s', 'entrepot' ), $api->name . ' ' . $api->version ),
				'url'   => add_query_arg( array(
					'page'     => 'entrepot-blocks',
					'action'   => 'update',
					'block'    => $block_id,
				), network_admin_url( 'admin.php' ) ),
				'nonce' => 'update-block_' . $block_id,
				'block' => $block_id,
				'api'   => $api,
			) ) );
			$upgrader->upgrade( $block_id );

			require ABSPATH . 'wp-admin/admin-footer.php';
			exit();

		// Delete a block
		} elseif ( 'delete' === $action ) {
			if ( ! current_user_can('entrepot_delete_blocks') ) {
				wp_die( __( 'Désolé, vous n’êtes pas autorisé·e à supprimer des blocs.', 'entrepot' ));
			}

			check_admin_referer( "$action-block_$block_id" );

			$deleted = entrepot_delete_block( $block_id );

			if ( is_wp_error( $deleted ) ) {
				wp_die( $deleted->get_error_message(), __( 'Erreur de suppression du bloc', 'entrepot' ), array(
					'back_link' => true,
				) );
			} else {
				$redirect = add_query_arg( 'deleted', $block_id, $redirect );
			}
		}

		wp_safe_redirect( $redirect );
		exit();
	}

	// During an update we do not need to load the full UI.
	if ( $in_iframe && ! isset( $_REQUEST['action'] ) ) {
		define( 'IFRAME_REQUEST', true );

		// Hide some admin UI parts for clarity.
		wp_add_inline_style( 'common', '
			#adminmenumain, #wpfooter { display: none; }
			#wpcontent { margin-left: 0; padding-left: 0; }
		' );

		require_once ABSPATH . 'wp-admin/admin-header.php';

		printf( '<div class="wrap">%s</div>', entrepot_admin_get_feedback_messages() );

		require ABSPATH . 'wp-admin/admin-footer.php';
		exit();
	} else {
		wp_enqueue_script( 'entrepot-manage-blocks' );
		add_thickbox();
		wp_enqueue_style( 'list-tables' );
		wp_add_inline_style( 'list-tables', '
			.block.plugin-card .column-compatibility ul {
				margin: 0;
			}

			.block.plugin-card .column-compatibility ul li {
				text-align: left;
			}

			.block.plugin-card .plugin-action-buttons a:not( .button ) {
				text-decoration: none;
				border: none;
			}
			.entrepot-blocks-loader {
				width: 100%;
				text-align: center;
				padding-top: 100px;
			}

			.entrepot-blocks-loader .spinner {
				display: inline-block;
				float: none;
			}
		' );
	}
}

/**
 * Blocks administration screen.
 *
 * @since 1.5.0
 */
function entrepot_admin_blocks() {
	printf(
		'<div class="wrap"><h1>%1$s</h1>%2$s<div id="entrepot-blocks"></div></div>',
		esc_html__( 'Types de bloc', 'entrepot' ),
		entrepot_admin_get_feedback_messages()
	);
}

/**
 * Get Blocks to update.
 *
 * @since 1.5.0
 *
 * @param  array $repositories The installed Block repositories.
 * @return array               The ones to update.
 */
function entrepot_blocks_get_updates( $repositories = array() ) {
	if ( ! $repositories ) {
		$repositories = entrepot_get_blocks();
	}

	$repositories_data = array();
	foreach ( $repositories as $slug => $block ) {
		$json = entrepot_get_repository_json( $slug, 'blocks' );

		if ( ! $json || ! isset( $json->releases ) ) {
			continue;
		}

		$response = entrepot_get_repository_latest_stable_release( $json->releases, array(
			'block'            => $json->name,
			'slug'             => $slug,
			'Version'          => $block->version,
			'GitHub Block URI' => str_replace( '/releases', '', $json->releases ),
		), 'block' );

		$repositories_data[ $block->id ] = $response;
	}

	$updated_repositories = wp_list_filter( $repositories_data, array( 'is_update' => true ) );

	if ( ! $updated_repositories ) {
		return null;
	}

	return $updated_repositories;
}

/**
 * Save Available Block updates information in a transient.
 *
 * @since 1.5.0
 */
function entrepot_blocks_update() {
	if ( wp_installing() ) {
		return;
	}

	$blocks  = entrepot_get_blocks();
	$current = get_site_transient( 'entrepot_update_blocks' );

	if ( ! is_object( $current ) ) {
		$current = new stdClass;
	}

	$new_option = new stdClass;
	$new_option->last_checked = time();

	$doing_cron = wp_doing_cron();
	$action     = current_action();

	$timeout = 12 * HOUR_IN_SECONDS;
	if ( $doing_cron ) {
		$timeout = 2 * HOUR_IN_SECONDS;
	}

	if ( 'upgrader_process_complete' === $action ) {
		$timeout = 0;
	} elseif ( 'load-toplevel_page_entrepot-blocks' === $action || 'load-update.php' === $action ) {
		$timeout = HOUR_IN_SECONDS;
	}

	if ( isset( $current->last_checked ) && $timeout > ( time() - $current->last_checked ) ) {
		$block_changed = false;
		foreach ( $blocks as $block ) {
			$new_option->checked[ $block->id ] = $block->version;

			if ( ! isset( $current->checked[ $block->id ] ) || strval( $current->checked[ $block->id ] ) !== strval( $block->version ) ) {
				$block_changed = true;
			}
		}

		if ( isset ( $current->response ) && is_array( $current->response ) ) {
			foreach ( $current->response as $block_id => $update_details ) {
				if ( ! isset( $blocks[ $block_id ] ) ) {
					$block_changed = true;
					break;
				}
			}
		}

		// Bail if we've checked recently and if nothing has changed
		if ( ! $block_changed ) {
			return;
		}
	}

	// Update last_checked for current to prevent multiple blocking requests if request hangs
	$current->last_checked = time();
	set_site_transient( 'entrepot_update_blocks', $current );

	// Get Block updates
	$updates = entrepot_blocks_get_updates( $blocks );

	if ( is_array( $updates ) ) {
		$new_option->response = $updates;
	} else {
		$new_option->response = array();
	}

	set_site_transient( 'entrepot_update_blocks', $new_option );
}

/**
 * Schedule a Cron Job to check for block updates.
 *
 * @since 1.5.0
 */
function entrepot_blocks_schedule_update_checks() {
	if ( ! wp_next_scheduled( 'entrepot_blocks_update' ) && ! wp_installing() ) {
		wp_schedule_event( time(), 'twicedaily', 'entrepot_blocks_update' );
	}
}

/** Hooks *******************************************************************/

// Register Block Types.
add_action( 'init', 'entrepot_register_block_types', 30 );

// Include Block Types PHP scripts.
add_action( 'plugins_loaded', 'entrepot_block_types_loaded', 20 );

// Handle custom capabilities.
add_filter( 'map_meta_cap', 'entrepot_blocks_map_custom_caps', 10, 4 );

// Create the Block Types Admin menu
if ( is_multisite() ) {
	add_action( 'network_admin_menu', 'entrepot_blocks_admin_menu'            );
	add_action( 'admin_bar_menu',     'entrepot_blocks_admin_bar_menu', 25, 1 );
} else {
	add_action( 'admin_menu', 'entrepot_blocks_admin_menu', 11 );
}

// Register scripts.
add_action( 'admin_init', 'entrepot_admin_blocks_register_scripts', 11 );

// Register Block Updater's cron job.
add_action( 'entrepot_admin_init', 'entrepot_blocks_schedule_update_checks', 1 );

// Trigger Block updates on Blocks screen load & cron event.
add_action( 'load-toplevel_page_entrepot-blocks', 'entrepot_blocks_update' );
add_action( 'entrepot_blocks_update',             'entrepot_blocks_update' );
