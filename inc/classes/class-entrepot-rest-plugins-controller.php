<?php
/**
 * Entrepôt's REST Plugins controller.
 *
 * @todo follow how progresses #9757 as it might create a REST Plugins controller
 * @see  https://core.trac.wordpress.org/ticket/9757
 *
 * @package Entrepôt\inc\classes
 *
 * @since 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_REST_Controller') ) {
	require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-controller.php';
}

/**
 * Class used to get a site's plugins via the REST API.
 *
 * @since 1.2.0
 *
 * @see WP_REST_Controller
 */
class Entrepot_REST_Plugins_Controller extends WP_REST_Controller {
	/**
	 * List of installed plugins.
	 *
	 * @var array
	 */
	protected $plugins;

	/**
	 * Current uploaded package
	 *
	 * @var File_Upload_Upgrader
	 */
	protected $upload;

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'plugins';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.2.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'args'                => array(),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<slug>[\w-]+)', array(
			'args' => array(
				'slug' => array(
					'description' => __( 'An alphanumeric identifier for the slug of the plugin.', 'entrepot' ),
					'type'        => 'string',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Checks if a given request has access to edit plugins.
	 *
	 * @since 1.2.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function permissions_check( $request ) {
		return current_user_can( 'update_plugins' );
	}

	/**
	 * Retrieves the collection of plugins.
	 *
	 * @since 1.2.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$plugins  = $this->get_installed_plugins();
		$response = array();
		$error    = new WP_Error( 'rest_plugin_overwrite_no_plugins', __( 'Aucune extension disponible.', 'entrepot' ), array( 'status' => 404 ) );

		if ( ! is_array( $plugins ) || ! $plugins ) {
			return $error;
		}

		foreach ( $plugins as $id => $plugin ) {
			// Entrepôt cannot be overwritten
			if ( 'entrepot/entrepot.php' === $id ) {
				continue;
			}

			$response[]  = $this->prepare_item_for_response( $plugin, $request );
		}

		if ( ! $response ) {
			return $error;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves the requested plugin.
	 *
	 * @since 1.2.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$plugins = $this->get_installed_plugins();
		$error   = new WP_Error( 'rest_plugin_not_found', __( 'L\'extension n\'est pas installée.', 'entrepot' ), array( 'status' => 404 ) );

		$slug = $request->get_param( 'slug' );

		if ( ! $slug ) {
			return $error;
		}

		$plugin = wp_list_filter( $plugins, array( 'slug' => $slug ) );

		if ( ! $plugin ) {
			return $error;
		}

		$response = $this->prepare_item_for_response( $plugin, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Temporarly overrides the update_plugins transient.
	 *
	 * @since  1.2.0
	 *
	 * @param  boolean        $transient False
	 * @return boolean|object            A local response object to shortcircuit the transient.
	 *                                   False otherwise.
	 */
	public function set_local_response( $transient = false ) {
		if ( empty( $this->upload->plugin ) || empty( $this->upload->package ) ) {
			return $transient;
		}

		$transient           = new stdClass;
		$transient->response = array( $this->upload->plugin => (object) array( 'package' => $this->upload->package ) );

		return $transient;
	}

	/**
	 * Checks if a package is valid & can replace the plugin.
	 *
	 * @since  1.2.0
	 *
	 * @param  string  $slug The plugin slug (which is also the Plugin dir name).
	 * @return boolean       True if the archive is valid & can replace the plugin.
	 *                       False otherwise.
	 */
	public function is_valid_archive( $slug = '' ) {
		$is_valid = false;
		$has_slug = null;

		if ( empty( $this->upload->package ) || ! $slug ) {
			return $is_valid;
		}

		$filetype = wp_check_filetype( $this->upload->package, array( 'zip' => 'application/zip' ) );

		if ( 'zip' !== $filetype['ext'] || 'application/zip' !== $filetype['type'] ) {
			return $is_valid;
		}

		if ( class_exists( 'ZipArchive' ) ) {
			$archive = new ZipArchive;

			if ( true === $archive->open( $this->upload->package ) ) {
				if ( $slug . '/' === $archive->getNameIndex( 0 ) ) {
					$is_valid = true;
				}

				$archive->close();
				$has_slug = false;
			}
		}

		if ( ! $is_valid && is_null( $has_slug ) ) {
			if ( ! class_exists( 'PclZip' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
			}

			$archive         = new PclZip( $this->upload->package );
			$archive_content = $archive->listContent();
			$archive_content = reset( $archive_content );

			if ( ! empty( $archive_content['filename'] ) && $slug . '/' === $archive_content['filename'] ) {
				$is_valid = true;
			}
		}

		return $is_valid;
	}

	/**
	 * Overwrites a plugin version with the one contained into the uploaded package.
	 *
	 * @since 1.2.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$files = $request->get_file_params();
		$id    = $request->get_param( 'id' );

		if ( ! $files || ! $id ) {
			return new WP_Error( 'rest_plugin_overwrite_no_data', __( 'Aucune donnée fournie.', 'entrepot' ), array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return new WP_Error( 'rest_plugin_overwrite_forbidden', __( 'Vous n\'êtes pas autorisé à mettre à jour des extensions.', 'entrepot' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$slug   = key( $files );
		$plugin = plugin_basename( sanitize_text_field( wp_unslash( $id ) ) );

		if ( $slug !== dirname( $plugin ) || 0 !== validate_file( $plugin ) ) {
			return new WP_Error( 'rest_plugin_overwrite_invalid_data', __( 'Les données fournies sont incohérentes.', 'entrepot' ), array( 'status' => 400 ) );
		}

		$status = array(
			'slug'       => $slug,
			'oldVersion' => '',
			'newVersion' => '',
			'message'    => '',
		);

		// Get the plugin version and name
		$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
		$status['name'] = $plugin_data['Name'];

		if ( $plugin_data['Version'] ) {
			$status['oldVersion'] = $plugin_data['Version'];
		}

		// Include needed files.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Creates a private attachment to use as the source zip.
		$this->upload         = new File_Upload_Upgrader( $slug, 'package' );
		$this->upload->plugin = $plugin;
		$result               = null;

		$is_valid = $this->is_valid_archive( $slug );

		if ( $is_valid ) {
			add_filter( 'pre_site_transient_update_plugins', array( $this, 'set_local_response' ), 10, 1 );

			/**
			 * From this point the process is very similar to a "shiny update"
			 *
			 * @see wp_ajax_update_plugin()
			 */
			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->bulk_upgrade( array( $plugin ) );

			remove_filter( 'pre_site_transient_update_plugins', array( $this, 'set_local_response' ), 10, 1 );
		}

		// Clean up the uploaded file
		$this->upload->cleanup();
		$this->upload = null;

		if ( is_null( $result ) || ! $is_valid ) {
			return new WP_Error( 'rest_plugin_overwrite_invalid_archive', __( 'L\'archive ZIP est manquante ou ne respecte pas l\'organisation de dossiers attendue', 'entrepot' ), array( 'status' => 500 ) );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $skin->result ) ) {
			return new WP_Error( $skin->result->get_error_code(), $skin->result->get_error_message(), array( 'status' => 500, 'data' => $status ) );

		} elseif ( $skin->get_errors()->get_error_code() ) {
			return new WP_Error( 'rest_plugin_overwrite_errors', $skin->get_error_messages(), array( 'status' => 500, 'data' => $status ) );

		// Plugin updated or reverted to the uploaded package version.
		} elseif ( is_array( $result ) && ! empty( $result[ $plugin ] ) ) {
			$plugin_data = get_plugins( '/' . $result[ $plugin ]['destination_name'] );
			$plugin_data = reset( $plugin_data );

			$status['message'] = esc_html__( 'La version de l\'extension est désormais celle l\'archive ZIP transmise', 'entrepot' );

			if ( $plugin_data['Version'] && $status['oldVersion'] ) {
				$status['newVersion'] = $plugin_data['Version'];

				if ( version_compare( $status['oldVersion'], $status['newVersion'], '<' ) ) {
					$status['message'] = sprintf(
						esc_html__( 'La version %1$s a été mise à jour pour la %2$s', 'entrepot' ),
						$status['oldVersion'],
						$status['newVersion']
					);
				} else {
					$status['message'] = sprintf(
						esc_html__( 'La version %1$s a été rétrogradée pour la %2$s', 'entrepot' ),
						$status['oldVersion'],
						$status['newVersion']
					);
				}
			}

			return rest_ensure_response( $status );

		} elseif ( false === $result ) {
			global $wp_filesystem;

			$error_code    = 'unable_to_connect_to_filesystem';
			$error_message = __( 'Impossible de se connecter au système de fichier. Merci de vérifier votre authenification', 'entrepot' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$error_message = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			return new WP_Error( $error_code, $error_message, array( 'status' => 500, 'data' => $status ) );
		}

		// An unhandled error occurred.
		return new WP_Error( 'rest_plugin_overwrite_failed', __( 'Le changement de version a échoué.', 'entrepot' ), array( 'status' => 400, 'data' => $status ) );
	}

	/**
	 * Prepares a single plugin output for response.
	 *
	 * @since 1.2.0
	 *
	 * @param array           $plugin  Array of Plugin header tags.
	 * @param WP_REST_Request $request Request object.
	 * @return array Array of plugin header tags.
	 */
	public function prepare_item_for_response( $plugin, $request ) {
		if ( ! is_array( $plugin ) ) {
			return array();
		}

		if ( 'id' !== key( $plugin ) ) {
			$plugin = reset( $plugin );
		}

		$schema     = $this->get_item_schema();
		$properties = wp_list_filter( $schema['properties'], array( 'type' => 'string' ) );
		$data       = array();
		$attributes = array();

		// Set plugin data to translate.
		foreach ( $properties as $property => $params ) {
			if ( 'id' === $property || 'slug' === $property ) {
				continue;
			}

			$data[ $params['description'] ] = $plugin[ $property ];
			$attributes[ $property ]        = $params['description'];
		}

		$data = _get_plugin_data_markup_translate( $plugin['id'], $data, false, true );
		$data = array_map( 'strip_tags', $data );

		// Update plugin with translated attributes
		foreach ( $attributes as $p => $t ) {
			if ( ! isset( $data[ $t ] ) ) {
				continue;
			}

			$plugin[ $p ] = $data[ $t ];

			if ( 'description' === $p ) {
				$plugin[ $p ] = wp_trim_words( $plugin[ $p ], 15 );
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$plugin = $this->filter_response_by_context( $plugin, $context );

		return $plugin;
	}

	/**
	 * Retrieves all of the installed plugins or the Rest additionnal shema.
	 *
	 * @since 1.2.0
	 *
	 * @param  boolean $schema True to get the additionnal schema.
	 * @return array Array of registered options.
	 */
	protected function get_installed_plugins( $schema = false ) {
		$rest_plugins = array();

		if ( ! $this->plugins ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$this->plugins = get_plugins();
		}

		foreach ( $this->plugins as $id => $args ) {
			if ( $schema ) {
				$rest_args = array();

				$default_schema = array(
					'type'        => 'string',
					'description' => '',
					'context'     => array( 'view', 'edit', 'embed' ),
				);

			} else {
				$rest_args = array( 'id' => $id );
				$slug      = dirname( $id );

				if ( ! $slug ) {
					continue;
				}

				if ( '.' === $slug ) {
					$slug = wp_basename( $id, '.php' );
				}

				$rest_args['slug'] = $slug;
			}

			$header_keys = array_keys( $args );

			foreach ( array_map( 'sanitize_key', $header_keys ) as $tag_id => $header_tag ) {
				if ( $schema && ! isset( $rest_plugins[ $header_tag ] ) ) {
					$rest_args['id']     = $header_tag;
					$rest_args['schema'] = wp_parse_args( array(
						'description' => $header_keys[ $tag_id ],
						'type'        => is_bool( $args[ $header_keys[ $tag_id ] ] ) ? 'boolean' : 'string',
					), $default_schema );

					$rest_plugins[ $rest_args['id'] ] = $rest_args;

				} else {
					$rest_args[ $header_tag ] = $args[ $header_keys[ $tag_id ] ];

					if ( 'githubpluginuri' === $header_tag ) {
						$repository = entrepot_get_repositories( wp_basename( $rest_args[ $header_tag ], '.git' ));

						if ( isset( $repository->icon ) ) {
							$rest_args['icon'] = $repository->icon;
						}
					}
				}
			}

			if ( ! $schema ) {
				if ( empty( $rest_args['icon'] ) ) {
					$rest_args['icon'] = esc_url( sprintf( 'https://ps.w.org/%s/assets/icon-256x256.png', $rest_args['slug'] ) );
				}

				$rest_plugins[ $rest_args['id'] ] = $rest_args;
			}
		}

		return $rest_plugins;
	}

	/**
	 * Retrieves the plugins schema, conforming to JSON Schema.
	 *
	 * @since 1.2.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$plugins = $this->get_installed_plugins( true );

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'plugins',
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'description' => __( 'An alphanumeric identifier for the object unique to its directory and main php file.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'slug'            => array(
					'description' => __( 'An alphanumeric identifier for the object unique to its slug.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		);

		foreach ( $plugins as $property_name => $property ) {
			$schema['properties'][ $property_name ] = $property['schema'];
		}

		return $this->add_additional_fields_schema( $schema );
	}
}
