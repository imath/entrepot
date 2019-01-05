<?php
/**
 * Entrepôt functions.
 *
 * @package Entrepôt\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gets the plugin's version.
 *
 * @since 1.0.0
 *
 * @return string The plugin's version.
 */
function entrepot_version() {
	return entrepot()->version;
}

/**
 * Gets the plugin's db version.
 *
 * @since 1.0.0
 *
 * @return string The plugin's db version.
 */
function entrepot_db_version() {
	return get_network_option( 0, '_entrepot_version', 0 );
}

/**
 * Gets the plugin's root path.
 *
 * @since 1.5.0
 *
 * @return string The plugin's root path.
 */
function entrepot_root_path() {
	return entrepot()->dir;
}

/**
 * Gets the plugin's root URL.
 *
 * @since 1.5.0
 *
 * @return string The plugin's root URL.
 */
function entrepot_root_url() {
	return entrepot()->url;
}

/**
 * Gets the plugin's assets folder URL.
 *
 * @since 1.0.0
 *
 * @return string The plugin's assets folder URL.
 */
function entrepot_assets_url() {
	return entrepot()->assets_url;
}

/**
 * Gets the plugin's assets folder path.
 *
 * @since 1.0.0
 *
 * @return string The assets folder path.
 */
function entrepot_assets_dir() {
	return entrepot()->assets_dir;
}

/**
 * Gets the plugin's JS folder URL.
 *
 * @since 1.0.0
 *
 * @return String The plugin's JS folder URL.
 */
function entrepot_js_url() {
	return entrepot()->js_url;
}

/**
 * Get the JS/CSS minified suffix.
 *
 * @since  1.0.0
 *
 * @return string the JS/CSS minified suffix.
 */
function entrepot_min_suffix() {
	$min = '.min';

	if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG )  {
		$min = '';
	}

	/**
	 * Filter here to edit the minified suffix.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $min The minified suffix.
	 */
	return apply_filters( 'entrepot_min_suffix', $min );
}

/**
 * Gets the Repositories' dir.
 *
 * @since 1.4.0
 *
 * @param  string $type The type of repositories to get. Default to 'plugins'.
 * @return string Path to the repositories dir.
 */
function entrepot_repositories_dir( $type = 'plugins' ) {
	/**
	 * Unit tests filter. Do not use.
	 *
	 * @since  1.4.0
	 *
	 * @param string $repositories_dir Path to the repositories dir.
	 * @param string $type             Repositories' type.
	 */
	$dir = apply_filters( 'entrepot_repositories_dir', trailingslashit( entrepot()->repositories_dir . $type ), $type );

	/**
	 * Use this filter to move somewhere else the Repositories dir.
	 *
	 * @since  1.0.0
	 * @since  1.4.0 The filter is now dynamic to take themes in account.
	 *
	 * @param string $dir Path to the repositories type dir.
	 */
	return apply_filters( "entrepot_{$type}_dir", $dir );
}

/**
 * Does the current site/network supports blocks.
 *
 * @since 1.5.0
 *
 * @return boolean True if the the current site/network supports blocks.
 *                 False otherwise.
 */
function entrepot_block_supports() {
	return entrepot()->block_supports;
}

/**
 * Loads translation.
 *
 * @since 1.0.0
 */
function entrepot_load_textdomain() {
	$entrepot = entrepot();
	load_plugin_textdomain( $entrepot->domain, false, trailingslashit( basename( $entrepot->dir ) ) . 'languages' );
}

/**
 * Adds the Entrepôt cache group.
 *
 * @since 1.0.0
 */
function entrepot_setup_cache_group() {
	wp_cache_add_global_groups( 'entrepot' );
}

/**
 * Gets all registered repositories or a specific one.
 *
 * @since 1.0.0
 * @since 1.4.0 Adds a new $type parameter to look for Theme repositories.
 *
 * @param  string $slug An empty string to get all repositories or
 *                      the repository slug to get a specific repository.
 * @param  string $type The type of repositories to get. Default to 'plugins'.
 * @return array|object The list of repository objects or a single repository object.
 */
function entrepot_get_repositories( $slug = '', $type = 'plugins' ) {
	$json = get_site_transient( "entrepot_registered_{$type}" );

	if ( ! $json ) {
		$file = sprintf( 'entrepot-%s.min.json', $type );

		// Try to get distant repositories list.
		if ( ! defined( 'PR_TESTING_ASSETS' ) ) {
			$uri = 'https://api.github.com/repos/imath/entrepot/contents/assets/' . $file;

			$request  = wp_remote_get( $uri, array(
				'timeout'    => 30,
				'user-agent' => 'Entrepôt/WordPress-Repositories-Fetcher; ' . get_bloginfo( 'url' ),
			) );

			if ( ! is_wp_error( $request ) && 200 === (int) wp_remote_retrieve_response_code( $request ) ) {
				$dist_repos = json_decode( wp_remote_retrieve_body( $request ) );
				$json       = base64_decode( $dist_repos->content );
			}
		}

		// Use local repositories by default.
		if ( ! $json ) {
			$src = sprintf( '%1$sentrepot-%2$s.min.json', entrepot_assets_dir(), $type );

			if ( ! file_exists( $src ) ) {
				return array();
			}

			$json = file_get_contents( $src );
		}

		if ( ! empty( $json ) ) {
			set_site_transient( "entrepot_registered_{$type}", $json, DAY_IN_SECONDS );
		}
	}

	// Set repositories
	$repositories = json_decode( $json );

	if ( ! is_array( $repositories ) ) {
		$repositories = array( $repositories );
	}

	if ( $slug ) {
		$single = false;

		foreach ( $repositories as $repository ) {
			if ( ! isset( $repository->releases ) ) {
				continue;
			}

			if ( $slug === entrepot_get_repository_slug( $repository->releases ) ) {
				$single = $repository;
				break;
			}
		}

		return $single;
	}

	return $repositories;
}

/**
 * Gets a specific repository's JSON data.
 *
 * @since 1.0.0
 * @since 1.4.0 Adds a new $type parameter to look for Theme repositories.
 *
 * @param  string $repository Name of the plugin or the theme.
 * @param  string $type       The type of repositories to get. Default to 'plugins'.
 * @return string             JSON data.
 */
function entrepot_get_repository_json( $repository = '', $type = 'plugins' ) {
	if ( ! $repository ) {
		return false;
	}

	// Specific to unit tests
	if ( defined( 'PR_TESTING_ASSETS' ) && PR_TESTING_ASSETS ) {
		$json = sprintf( '%1$s%2$s.json', entrepot_repositories_dir( $type ), sanitize_file_name( $repository ) );
		if ( ! file_exists( $json ) ) {
			return false;
		}

		$data = file_get_contents( $json );
		return json_decode( $data );
	}

	return entrepot_get_repositories( $repository, $type );
}

/**
 * Gets the repository's slug of a given path.
 *
 * @since 1.0.0
 *
 * @param  string $path Path to the repository.
 * @return string       The repository's slug.
 */
function entrepot_get_repository_slug( $path = '' ) {
	if ( ! $path ) {
		return false;
	}

	return wp_basename( dirname( $path ) );
}

/**
 * Make sure a version string is standardized.
 *
 * @since 1.1.0
 *
 * @param  string $version The version number to standardize.
 * @return false|string    The standardized version or false on fail.
 */
function entrepot_get_standardized_version_number( $version = '' ) {
	$return        = false;
	$version_count = count( explode( '.', $version ) );

	if ( 3 === $version_count ) {
		$return = $version;

	} elseif ( 3 > $version_count ) {
		while ( 3 !== count( explode( '.', $version ) ) ) {
			$version .= '.0';
		}

		$return = $version;
	} else {
		error_log( 'Version numbers should be standardized like \'1.0.0\'' );
	}

	return $version;
}

/**
 * Checks with the GitHub releases of the Repository if there a new stable version available.
 *
 * @since 1.0.0
 *
 * @param  string $atom_url   The Repository's feed URL.
 * @param  array  $repository The plugin's data.
 * @param  string $type       The repository type (plugin or theme).
 * @return object             The stable release data.
 */
function entrepot_get_repository_latest_stable_release( $atom_url = '', $repository = array(), $type = 'plugin' ) {
	$tag_data            = new stdClass;
	$tag_data->is_update = false;
	$github_uri_key      = sprintf( 'GitHub %s URI', ucfirst( $type ) );

	if ( ! $atom_url  ) {
		// For Unit Testing purpose only. Do not use this constant in your code.
		if ( defined( 'PR_TESTING_ASSETS' ) && isset( $repository['slug'] ) ) {
			if ( 'entrepot' === $repository['slug'] ) {
				$test_releases = 'releases';
			} else {
				$test_releases = $repository['slug'];
			}
			$atom_url = trailingslashit( entrepot()->dir ) . 'tests/phpunit/assets/' . $test_releases;
		} else {
			return $tag_data;
		}
	}

	$atom_url = rtrim( $atom_url, '.atom' ) . '.atom';
	$atom = new Entrepot_Atom( $atom_url );

	if ( ! isset( $atom->feed ) || ! isset( $atom->feed->entries ) ) {
		return $tag_data;
	}

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

		$response = array(
			'id'          => $release->id,
			'slug'        => '',
			'plugin'      => '',
			'new_version' => $tag,
			'url'         => '',
			'package'     => '',
		);

		if ( 'theme' === $type || 'block' === $type ) {
			unset( $response['plugin'] );

			if ( 'theme' === $type ) {
				$response['theme'] = '';
			} else {
				$response['block'] = '';
			}
		}

		if ( ! empty( $repository['Version'] ) ) {
			$std_tag     = entrepot_get_standardized_version_number( $tag );
			$std_version = entrepot_get_standardized_version_number( $repository['Version'] );

			if ( ! $std_tag || ! $std_version || version_compare( $std_tag, $std_version, '<=' ) ) {
				continue;
			}

			$response = wp_parse_args( array(
				'id'          => rtrim( str_replace( array( 'https://', 'http://' ), '', $repository[ $github_uri_key ] ), '/' ),
				'slug'        => $repository['slug'],
				'url'         => $repository[ $github_uri_key ],
				'package'     => sprintf( '%1$sreleases/download/%2$s/%3$s',
					trailingslashit( $repository[ $github_uri_key ] ),
					$tag,
					sanitize_file_name( $repository['slug'] . '.zip' )
				),
			), $response );

			if ( 'theme' === $type ) {
				$response['theme'] = $repository['theme'];
			} elseif ( 'block' === $type ) {
				$response['block'] = $repository['block'];
			} else {
				$response['plugin'] = $repository['plugin'];
			}

			if ( ! empty( $release->content ) ) {
				$tag_data->full_upgrade_notice = end( $release->content );

				if ( 'theme' === $type && 'latest' !== $repository['Version'] ) {
					$response['url'] = add_query_arg(
						array(
							'page'        => 'repositories',
							'section'     => 'changelog',
							'theme'       => $repository['slug'],
						), network_admin_url( 'themes.php' )
					);
				}
			}

			if ( 'latest' === $repository['Version'] ) {
				$response['download_link'] = $response['package'];
				$response['version']       = $response['new_version'];
				$response['name']          = $response['slug'];

				// Specific to themes.
				if ( 'theme' === $type ) {
					$theme_data = entrepot_get_repositories( $response['slug'], 'themes' );

					$response['preview_url'] = '';
					if ( ! empty( $theme_data->urls->preview_url ) ) {
						$response['preview_url'] = set_url_scheme( $theme_data->urls->preview_url );
					}

					$response['author'] = array();
					if ( ! empty( $theme_data->author ) ) {
						$response['author'] = array(
							'user_nicename' => $theme_data->author,
							'display_name'  => $theme_data->author,
						);

						if ( isset( $theme->urls->author_uri ) ) {
							$response['author']['profile'] = $theme->urls->author_uri;
						}
					}

					$response['screenshot_url'] = $theme->screenshot;
				}

				$tag_data->is_install = true;
			} else {
				$tag_data->is_update = true;

				$repository_data = entrepot_get_repositories( $response['slug'] );

				if ( ! empty( $repository_data->icon ) ) {
					$tag_data->icons = array( '1x' => esc_url_raw( $repository_data->icon ) );
				}
			}
		}

		foreach ( $response as $k => $v ) {
			$tag_data->{$k} = $v;
		}

		break;
	}

	return $tag_data;
}

/**
 * Adds a new Plugin's header tag to ease repositories identification
 * within the regular plugins.
 *
 * @since 1.4.0
 *
 * @param  array  $headers  The current Plugin's header tag.
 * @return array            The Plugin repositories header tag.
 */
function entrepot_plugin_extra_header( $headers = array() ) {
	if (  ! isset( $headers['GitHub Plugin URI'] ) ) {
		$headers['GitHub Plugin URI'] = 'GitHub Plugin URI';
	}

	$headers['Allow File Edits'] = 'Allow File Edits';

	return $headers;
}

/**
 * Adds a new Theme's header tag to ease repositories identification
 * within the regular themes.
 *
 * @since 1.4.0
 *
 * @param  array  $headers  The current Theme's header tag.
 * @return array            The Theme repositories header tag.
 */
function entrepot_theme_extra_header( $headers = array() ) {
	if (  ! isset( $headers['GitHub Theme URI'] ) ) {
		$headers['GitHub Theme URI'] = 'GitHub Theme URI';
	}

	return $headers;
}

/**
 * Gets all installed repositories.
 *
 * @since 1.0.0
 * @since 1.4.0 Add a $type parameter
 *
 * @return array The repositories list.
 */
function entrepot_get_installed_repositories( $type = 'plugins' ) {
	$repositories = array();

	if ( defined( 'PR_TESTING_ASSETS' ) && has_filter( 'entrepot_get_installed_repositories' ) ) {
		return apply_filters( 'entrepot_get_installed_repositories', $repositories, $type );
	}

	if ( 'themes' === $type ) {
		$themes = wp_get_themes();

		foreach ( $themes as $kt => $vt ) {
			$github_uri = $vt->get( 'GitHub Theme URI' );

			if ( ! $github_uri ) {
				continue;
			}

			// Properties are private
			$repositories[ $kt ] = array(
				'theme'            => $kt,
				'Name'             => $vt->get( 'Name' ),
				'Version'          => $vt->get( 'Version' ),
				'GitHub Theme URI' => $github_uri,
			);
		}

	} else {
		$plugins      = get_plugins();
		$repositories = array_diff_key( $plugins, wp_list_filter( $plugins, array( 'GitHub Plugin URI' => '' ) ) );
	}

	return $repositories;
}

/**
 * Manage repositories Upgrades by overriding the update_plugins transient.
 *
 * @since 1.0.0
 * @deprecated 1.4.0
 *
 * @param  object $option The update_plugins transient value.
 * @return object         The update_plugins transient value.
 */
function entrepot_update_repositories( $option = null ) {
	return entrepot_update_plugin_repositories( $option );
}

/**
 * Manage Plugin repository Updates by overriding the update_plugins transient.
 *
 * @since 1.4.0
 *
 * @param  object $option The update_plugins transient value.
 * @return object         The update_plugins transient value.
 */
function entrepot_update_plugin_repositories( $option = null ) {
	// Only do it when a WordPress.org request happened.
	if ( ! did_action( 'http_api_debug' ) ) {
		return;
	}

	$repositories = entrepot_get_installed_repositories();

	$repositories_data = array();
	foreach ( $repositories as $kr => $dp ) {
		$repository_name = trim( dirname( $kr ), '/' );
		$json = entrepot_get_repository_json( $repository_name );

		if ( ! $json || ! isset( $json->releases ) ) {
			continue;
		}

		$response = entrepot_get_repository_latest_stable_release( $json->releases, array_merge( $dp, array(
			'plugin' => $kr,
			'slug'   => $repository_name,
		) ), 'plugin' );

		$repositories_data[ $kr ] = $response;
	}

	$updated_repositories = wp_list_filter( $repositories_data, array( 'is_update' => true ) );

	if ( ! $updated_repositories ) {
		return;
	}

	if ( isset( $option->response ) ) {
		$option->response = array_merge( $option->response, $updated_repositories );
	} else {
		$option->response = $repositories_data;
	}

	// Prevent infinite loops.
	remove_action( 'set_site_transient_update_plugins', 'entrepot_update_plugin_repositories', 10, 1 );

	set_site_transient( 'update_plugins', $option );
}

/**
 * Manage Theme repository Updates by overriding the update_themes transient.
 *
 * @since 1.4.0
 *
 * @param  object $option The update_themes transient value.
 * @return object         The update_themes transient value.
 */
function entrepot_update_theme_repositories( $option = null ) {
	// Only do it when a WordPress.org request happened.
	if ( ! did_action( 'http_api_debug' ) ) {
		return;
	}

	$repositories = entrepot_get_installed_repositories( 'themes' );

	if ( ! $repositories ) {
		return;
	}

	$repositories_data = array();
	foreach ( $repositories as $kr => $dp ) {
		$json = entrepot_get_repository_json( $kr, 'themes' );

		if ( ! $json || ! isset( $json->releases ) ) {
			continue;
		}

		$response = entrepot_get_repository_latest_stable_release( $json->releases, array_merge( $dp, array(
			'slug'   => $kr,
		) ), 'theme' );

		// Themes, unlike Plugins do not use objects
		$repositories_data[ $kr ] = (array) $response;
	}

	$updated_repositories = wp_list_filter( $repositories_data, array( 'is_update' => true ) );

	if ( ! $updated_repositories ) {
		return;
	}

	if ( isset( $option->response ) ) {
		$option->response = array_merge( $option->response, $updated_repositories );
	} else {
		$option->response = $repositories_data;
	}

	// Prevent infinite loops.
	remove_action( 'set_site_transient_update_themes', 'entrepot_update_theme_repositories', 10, 1 );

	set_site_transient( 'update_themes', $option );
}

/**
 * Sanitize repositiories headers the way it's done for Plugins.
 *
 * @since 1.0.0
 *
 * @param  string $text The text to sanitize.
 * @return string       The sanitized text.
 */
function entrepot_sanitize_repository_text( $text = '' ) {
	return wp_kses( $text, array(
		'a' => array( 'href' => array(),'title' => array(), 'target' => array() ),
		'abbr' => array( 'title' => array() ),'acronym' => array( 'title' => array() ),
		'code' => array(), 'pre' => array(), 'em' => array(),'strong' => array(),
		'ul' => array(), 'ol' => array(), 'li' => array(), 'p' => array(), 'br' => array()
	) );
}

/**
 * Sanitize the repository's content.
 *
 * @since 1.0.0
 *
 * @param  string $content The content to sanitized.
 * @return string          The sanitized content.
 */
function entrepot_sanitize_repository_content( $content = '' ) {
	return wp_kses( $content, array_intersect_key( $GLOBALS['allowedposttags'], array(
		'h1' => true, 'h2' => true, 'h3' => true, 'h4' => true, 'h5' => true, 'h6' => true,
		'ul' => true, 'ol' => true, 'li' => true, 'table' => true, 'tr' => true, 'td' => true,
		'thead' => true, 'tbody' => true, 'tfoot' => true, 'blockquote' => true, 'a' => true, 'img' => true,
		'pre' => true, 'code' => true, 'p' => true, 'strong' => true, 'bold' => true, 'em' => true, 'i' => true,
	) ) );
}

/**
 * Checks each dependency & returns the ones that are not satisfied.
 *
 * @since 1.1.0
 *
 * @param  array  $dependencies An array of objects.
 * @return array                An empty array or the list of unsatisfied dependencies.
 */
function entrepot_get_repository_dependencies( $dependencies = array() ) {
	if ( ! $dependencies ) {
		return array();
	}

	$dependencies_data = array();

	$d = array_map( 'get_object_vars', $dependencies );
	foreach ( $d as $kd => $dependency ) {
		if ( function_exists( key( $dependency ) ) ) {
			continue;
		}

		$dependencies_data[] = reset( $dependency );
	}

	return $dependencies_data;
}

/**
 * Registers new upgrade tasks.
 *
 * @see https://github.com/imath/entrepot/wiki/03-Enjoying-the-Entrepôt-Upgrade-API for more infos.
 *
 * @since 1.1.0
 *
 * @param  string  $slug       The Entrepôt registered plugin slug. Required.
 * @param  string  $db_version The Current DB version for the Entrepôt registered plugin slug. Required.
 * @param  array   $tasks      The list of upgrade tasks for the Entrepôt registered plugin. Required.
 * @return boolean             True if the Tasks were successfully registered false otherwise.
 */
function entrepot_register_upgrade_tasks( $slug = '', $db_version = '', $tasks = array() ) {
	$entrepot = entrepot();

	if ( ! $slug || ! $db_version || ! $tasks ) {
		return false;
	}

	if ( isset( $entrepot->upgrades[ $slug ] ) ) {
		return false;
	}

	/**
	 * NB: Tasks should be an array keyed by version number containing arrays of parameters
	 *
	 * eg: array( '1.1.0', array(
	 * 	array(
	 * 	 @type string  $callback The Upgrade routine
	 *	 @type integer $count    The total number of items to upgrade
	 *	 @type string  $message  The message to display in the progress bar
	 *	 @type integer $number   Number of items to upgrade per ajax request,
	 * 	),
	 * 	array(
	 * 	 @type string  $callback The Upgrade routine
	 *	 @type integer $count    The total number of items to upgrade
	 *	 @type string  $message  The message to display in the progress bar
	 *	 @type integer $number   Number of items to upgrade per ajax request,
	 * 	),
	 * 	etc..
	 * ) );
	 */
	$entrepot->upgrades = array_merge( $entrepot->upgrades, array( $slug => (object) array(
		'slug'       => $slug,
		'db_version' => $db_version,
		'tasks'      => (array) $tasks,
	) ) );

	return true;
}

/**
 * Unregister a specific plugin's upgrade tasks.
 *
 * @since 1.1.0
 *
 * @param  string  $slug The Entrepôt registered plugin slug. Required.
 * @return boolean       True if the Tasks were successfully unregistered false otherwise.
 */
function entrepot_unregister_upgrade_tasks( $slug = '' ) {
	$entrepot = entrepot();

	if ( ! $slug || ! isset( $entrepot->upgrades[ $slug ] ) ) {
		return false;
	}

	unset( $entrepot->upgrades[ $slug ] );
	return true;
}

/**
 * Gets the repositories upgrade tasks.
 *
 * @since 1.1.0
 *
 * @return array The list of upgrade tasks to perform for each repository.
 */
function entrepot_get_upgrader_tasks() {
	$upgrade = array();

	/**
	 * Hook here to register your uprade tasks.
	 *
	 * @since 1.1.0
	 */
	do_action( 'entrepot_register_upgrade_tasks' );

	/**
	 * Filter here to populate your upgrade tasks.
	 *
	 * @since 1.1.0
	 *
	 * @param array $value list of tasks to perform
	 *
	 * eg array( $slug => array(
	 * 		$slug        string The plugin slug
	 * 		$db_version  string The current database version (before the upgrade)
	 *		$tasks       array (
	 *			A list of arguments for each plugin version
	 *			$version array (
	 * 			 @type string  $callback The Upgrade routine
	 *			 @type integer $count    The total number of items to upgrade
	 *			 @type string  $message  The message to display in the progress bar
	 *			 @type integer $number   Number of items to upgrade per ajax request,
	 *      )
	 *    )
	 * ) )
	 */
	$tasks = (array) apply_filters( 'entrepot_add_upgrader_tasks', entrepot()->upgrades );

	foreach ( $tasks as $t ) {
		$repository = entrepot_get_repository_json( $t->slug );

		if ( ! $repository ) {
			continue;
		}

		foreach ( $t->tasks as $version => $list ) {
			if ( version_compare( $version, $t->db_version, '>' ) ) {
				if ( empty( $upgrade[ $t->slug ] ) ) {
					$upgrade[ $t->slug ]['tasks'] = $list;
				} else {
					$upgrade[ $t->slug ]['tasks'] = array_merge( $upgrade[ $t->slug ]['tasks'], $list );
				}
			}
		}

		if ( ! empty( $upgrade[ $t->slug ]['tasks'] ) ) {
			$upgrade[ $t->slug ]['info'] = array_intersect_key( get_object_vars( $repository ), array(
				'name' => true,
				'icon' => true,
				'slug' => true,
			) );

			if ( empty( $upgrade[ $t->slug ]['info']['icon'] ) ) {
				$upgrade[ $t->slug ]['info']['icon'] = esc_url_raw( entrepot_assets_url() . 'repo.svg' );
			}
		}
	}

	return $upgrade;
}

/**
 * Registers a rest route for the Installed plugins.
 *
 * @since 1.2.0
 */
function entrepot_rest_routes() {
	// Plugins.
	$controller = new Entrepot_REST_Plugins_Controller;
	$controller->register_routes();

	// Blocks
	if ( entrepot_block_supports() ) {
		$controller = new Entrepot_REST_Blocks_Controller;
		$controller->register_routes();
	}
}
