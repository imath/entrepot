<?php
/**
 * Entrepôt's REST Blocks controller.
 *
 * @package Entrepôt\inc\classes
 *
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_REST_Controller') ) {
	require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-controller.php';
}

/**
 * Class used to manage a site's block types via the REST API.
 *
 * @since 1.5.0
 *
 * @see WP_REST_Controller
 */
class Entrepot_REST_Blocks_Controller extends WP_REST_Controller {
	/**
	 * List of installed block types.
	 *
	 * @var array
	 */
	protected $blocks;

	/**
	 * Constructor.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'entrepot-blocks';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.5.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'args'                => $this->get_collection_params(),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Checks if a given request has access to edit block types.
	 *
	 * @since 1.5.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function permissions_check( $request ) {
		return current_user_can( 'update_entrepot_blocks' );
	}

	/**
	 * Retrieves the collection of block types.
	 *
	 * @since 1.5.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		// Retrieve the list of registered collection query parameters.
		$registered  = $this->get_collection_params();
		$params      = array( 'tab' => 'installed' );

		foreach ( $request->get_params() as $key => $param ) {
			if ( ! isset( $registered[ $key ] ) ) {
				continue;
			}

			$params[ $key ] = $param;
		}

		// List blocks correponding to the current tab.
		$blocks   = $this->get_blocks( $params['tab'] );
		$response = array();
		$error    = new WP_Error( 'rest_entrepot_blocks_no_blocks', __( 'Aucun type de bloc disponible.', 'entrepot' ), array( 'status' => 404 ) );

		if ( ! is_array( $blocks ) || ! $blocks ) {
			return $error;
		}

		foreach ( $blocks as $id => $block ) {
			$data       = $this->prepare_item_for_response( $block, $request );
			$response[] = $this->prepare_response_for_collection( $data );
		}

		if ( ! $response ) {
			return $error;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 1.5.0
	 *
	 * @param array $block The block type data.
	 * @param string $type Whether the block is installed or available.
	 * @return array Links for the given block type.
	 */
	protected function prepare_links( $block, $type = 'installed' ) {
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self' => array(
				'href'   => rest_url( trailingslashit( $base ) . $block['id'] ),
			),
			'collection' => array(
				'href'   => rest_url( $base ),
			),
			'block_information' => array(
				'href' => network_admin_url( sprintf( 'admin.php?page=entrepot-blocks&amp;action=block-information&amp;block=%s&amp;TB_iframe=true&amp;width=600&amp;height=550', $block['id'] ) ),
			),
		);

		$delete_link = array(
			'href'       => add_query_arg( array(
				'page'     => 'entrepot-blocks',
				'_wpnonce' => wp_create_nonce( 'delete-block_' . $block['id'] ),
				'action'   => 'delete',
				'block'    => $block['id'],
			), network_admin_url( 'admin.php' ) ),
			'embeddable' => true,
			'title'      => __( 'Supprimer', 'entrepot' ),
			'classes'    => 'delete-now attention',
			'confirm'    => __( 'Êtes-vous certain·e de vouloir supprimer ce bloc ? Cette action ne peut être annulée.', 'entrepot' ),
		);

		if ( 'installed' === $type ) {
			$active_blocks = get_site_option( 'entrepot_active_blocks', array() );
			$block_updates = get_site_transient( 'entrepot_update_blocks' );

			// Include update links for Blocks needing to be updated.
			if ( current_user_can( 'update_entrepot_blocks' ) && isset( $block_updates->response[ $block['id'] ] ) && $block_updates->response[ $block['id'] ] ) {
				$links = array_merge( $links, array(
					'update'    => array(
						'href'       => add_query_arg( array(
							'page'     => 'entrepot-blocks',
							'_wpnonce' => wp_create_nonce( 'update-block_' . $block['id'] ),
							'action'   => 'update',
							'block'    => $block['id'],
						), network_admin_url( 'admin.php' ) ),
						'embeddable' => true,
						'title'      => __( 'Mettre à jour', 'entrepot' ),
						'classes'    => 'update-now button',
					),
					'changelog' => array(
						'href'    => network_admin_url( sprintf( 'admin.php?page=entrepot-blocks&amp;action=block-information&amp;block=%s&amp;section=changelog&amp;TB_iframe=true&amp;width=600&amp;height=550', $block['id'] ) ),
						'title'   => __( 'Notes de version', 'entrepot' ),
						'classes' => 'thickbox open-plugin-details-modal',
					),
				) );
			}

			if ( in_array( $block['id'], $active_blocks, true ) ) {
				// Deactivate blocks if a dependency is not satisfied
				if ( isset( $block['dependencies'] ) && $block['dependencies'] ) {
					entrepot_deactivate_block( $block['id'] );

					/**
					 * Make sure there's no need to refresh the page
					 * to have the delete link displayed.
					 */
					$links['delete'] = $delete_link;
				} elseif ( ! isset( $links['update'] ) ) {
					$links['deactivate'] = array(
						'href'       => add_query_arg( array(
							'page'     => 'entrepot-blocks',
							'_wpnonce' => wp_create_nonce( 'deactivate-block_' . $block['id'] ),
							'action'   => 'deactivate',
							'block'    => $block['id'],
						), network_admin_url( 'admin.php' ) ),
						'embeddable' => true,
						'title'      => __( 'Désactiver', 'entrepot' ),
						'classes'    => 'deactivate-now button',
					);
				}
			} elseif ( ! isset( $links['update'] ) ) {
				$links['activate'] = array(
					'href'       => add_query_arg( array(
						'page'     => 'entrepot-blocks',
						'_wpnonce' => wp_create_nonce( 'activate-block_' . $block['id'] ),
						'action'   => 'activate',
						'block'    => $block['id'],
					), network_admin_url( 'admin.php' ) ),
					'embeddable' => true,
					'title'      => __( 'Activer', 'entrepot' ),
					'classes'    => 'activate-now button-primary button',
				);
				$links['delete'] = $delete_link;
			} else {
				$links['delete'] = $delete_link;
			}
		} else {
			$links['install'] = array(
				'href'       => add_query_arg( array(
					'page'     => 'entrepot-blocks',
					'_wpnonce' => wp_create_nonce( 'install-block_' . $block['id'] ),
					'action'   => 'install',
					'block'    => $block['id'],
				), network_admin_url( 'admin.php' ) ),
				'embeddable' => true,
				'title'      => __( 'Installer', 'entrepot' ),
				'classes'    => 'install-now button',
			);
		}

		if ( isset( $block['dependencies'] ) && $block['dependencies'] ) {
			$links = array_intersect_key( $links, array(
				'delete'            => true,
				'self'              => true,
				'collection'        => true,
				'block_information' => true,
				'update'            => true,
				'changelog'         => true,
			) );
		}

		return $links;
	}

	/**
	 * Prepares a single block type output for response.
	 *
	 * @since 1.5.0
	 *
	 * @param array $block Block type data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object for the Block type data.
	 */
	public function prepare_item_for_response( $block, $request ) {
		if ( ! is_array( $block ) ) {
			return array();
        }

		// Get available fields.
		$fields = $this->get_fields_for_response( $request );

		// Sanitize Block fields data.
		foreach ( $fields as $property ) {
			if ( ! isset( $block[ $property ] ) ) {
				continue;
			}

			if ( in_array( $property, array( 'icon', 'releases', 'README', 'urls' ) ) ) {
				if ( 'urls' === $property ) {
					foreach ( $property as $property_key => $property_value ) {
						$block[ $property ]->{$property_key} = esc_url_raw( $property_value );
					}
				} else {
					$block[ $property ] = esc_url_raw( $block[ $property ] );
				}
			} elseif ( in_array( $property, array( 'name', 'slug', 'author', 'description' ) ) ) {
				$property_value = $block[ $property ];
				if ( 'description' === $property ) {
					$property_value = wp_trim_words( $property_value, 15 );
				}

				$block[ $property ] = strip_tags( $property_value );
			} elseif ( 'tags' === $property ) {
				$block[ $property ] = array_map( 'santize_text_field', $block[ $property ] );
			} elseif ( 'dependencies' === $property ) {
				$block[ $property ] = entrepot_get_repository_dependencies( $block[ $property ] );
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$block = $this->add_additional_fields_to_object( $block, $request );
		$block = $this->filter_response_by_context( $block, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $block );

		$links = $this->prepare_links( $block, $request['tab'] );
		$response->add_links( $links );

		return $response;
	}

	/**
	 * Translate the block type's description.
	 *
	 * @since 1.5.0
	 *
	 * @return object $description The available localized descriptions.
	 * @return string The translated block type's description.
	 */
	protected function translate_block_description( $description = null ) {
		$locale = get_user_locale();
		if ( ! $locale ) {
			$locale = 'en_US';
		}

		if ( isset( $description->{$locale} ) ) {
			return $description->{$locale};
		} elseif ( isset( $description->en_US ) ) {
			return $description->en_US;
		}

		return __( 'Aucune description fournie.', 'entrepot' );
	}

	/**
	 * Retrieves installed or available block types.
	 *
	 * @since 1.5.0
	 *
	 * @param  string $tab The type of blocks to get (installed or available).
	 * @return array array The list of matching block types.
	 */
	protected function get_blocks( $tab = 'installed' ) {
		$rest_blocks     = array();
		$entrepot_blocks = entrepot_get_block_repositories();

		if ( ! $this->blocks ) {
			$this->blocks = entrepot_get_blocks();
		}

		$installed_block_ids = wp_list_pluck( $this->blocks, 'id' );

		foreach ( $entrepot_blocks as $block ) {
			if ( ! isset( $block->slug ) || ! isset( $block->author ) ) {
				continue;
			}

			$block_id = $block->author . '/' . $block->slug;

			// Do not display the testing block when WP Debug is off.
			if ( ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) && 'imath/entrepot-test-block' === $block_id ) {
				continue;
			}

			$is_installed = in_array( $block_id, $installed_block_ids, true );

			// Depending on the active tab, fetch the right blocks.
			if ( ( 'installed' === $tab && ! $is_installed ) || ( 'available' === $tab && $is_installed ) ) {
				continue;
			}

			// Set the ID of the block type.
			$block->id = $block_id;
			$block->github_url = sprintf( 'https://github.com/%1$s/%2$s.git', $block->author, $block->slug );

			$rest_blocks[ $block->slug ] = (array) $block;
			if ( isset( $rest_blocks[ $block->slug ]['description'] ) ) {
				$rest_blocks[ $block->slug ]['description'] = $this->translate_block_description( $rest_blocks[ $block->slug ]['description'] );
			}

			if ( empty( $rest_blocks[ $block->slug ]['icon'] ) ) {
				$rest_blocks[ $block->slug ]['icon'] = esc_url( trailingslashit( entrepot_assets_url() ) . 'block.svg' );
			}
		}

		// Append installed blocks even if unregistered.
		if ( 'installed' === $tab ) {
			$unregistered_blocks = array_diff( array_flip( $installed_block_ids ), array_keys( $rest_blocks ) );

			if ( $unregistered_blocks ) {
				foreach ( $unregistered_blocks as $unregistered_block_id => $unregistered_block_slug ) {
					$rest_blocks[ $unregistered_block_slug ] = array(
						'id'          => $unregistered_block_id,
						'slug'        => $unregistered_block_slug,
						'icon'        => trailingslashit( entrepot_assets_url() ) . 'block.svg',
						'description' => $this->translate_block_description( '' ),
					);

					if ( isset( $this->blocks[ $unregistered_block_slug ]->name ) ) {
						$rest_blocks[ $unregistered_block_slug ]['name'] = $this->blocks[ $unregistered_block_slug ]->name;
					}

					if ( isset( $this->blocks[ $unregistered_block_slug ]->author ) ) {
						$rest_blocks[ $unregistered_block_slug ]['author'] = $this->blocks[ $unregistered_block_slug ]->author;
					}
				}
			}
		}

		return $rest_blocks;
	}

	/**
	 * Retrieves the block types schema, conforming to JSON Schema.
	 *
	 * @since 1.5.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'entrepot_blocks',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Un identifiant alphanumérique unique par rapport au nom de l’auteur et du type de bloc.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'slug' => array(
					'description' => __( 'Un identifiant alphanumérique unique par rapport à la terminaison de l’URL GitHub du type de bloc.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'Le nom du type de bloc.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'author' => array(
					'description' => __( 'Le nom d’utilisateur GitHub de l’auteur du type de bloc.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'icon' => array(
					'description' => __( 'L’URL vers le fichier image de l’icône représentant le type de bloc.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'tags' => array(
					'description' => __( 'La liste des étiquettes qui caractérisent le type de bloc.', 'entrepot' ),
					'type'        => 'array',
					'items'       => array(
						'type'    => 'string',
					),
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'dependencies' => array(
					'description' => __( 'Une liste d’objets ayant pour nom de propriété le nom de la fonction requise et pour valeur de propriété le nom de la version de WordPress de l’extension ou du thème correspondant pour le type de bloc.', 'entrepot' ),
					'type'        => 'array',
					'items'       => array(
						'type'    => 'object',
					),
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'releases' => array(
					'description' => __( 'L’URL vers la liste des versions disponibles pour le type de bloc.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'description' => array(
					'description' => __( 'Un object ayant pour noms de propriété les locales disponibles et valeurs la description correspondante pour le type de bloc.', 'entrepot' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'README' => array(
					'description' => __( 'L’URL vers le fichier README.md du dépôt GitHub du type de bloc.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'urls' => array(
					'description' => __( 'Un object ayant pour noms de propriété les sections d’information disponibles et leurs URLs correspondantes pour le type de bloc.', 'entrepot' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Retrieves the query params for the block types collection.
	 *
	 * @since 1.5.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'edit';

		$query_params['tab'] = array(
			'description' => __( 'Limiter les blocks retournés à ceux qui correspondent à l’onglet actif.', 'entrepot' ),
			'type'        => 'string',
			'default'     => 'installed',
			'enum'        => array( 'installed', 'available' ),
		);

		return $query_params;
	}
}
