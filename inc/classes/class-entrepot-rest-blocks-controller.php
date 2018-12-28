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
	 * Current uploaded package
	 *
	 * @var File_Upload_Upgrader
	 */
	protected $upload;

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

		$blocks   = $this->get_installed_blocks();
		$response = array();
		$error    = new WP_Error( 'rest_entrepot_blocks_no_blocks', __( 'Aucun type de bloc disponible.', 'entrepot' ), array( 'status' => 404 ) );

		if ( ! is_array( $blocks ) || ! $blocks ) {
			return $error;
		}

		// @todo list all available blocks.
		if ( $params['tab'] !== 'installed' ) {
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
	 * @return array Links for the given block type.
	 */
	protected function prepare_links( $block ) {
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self' => array(
				'href'   => rest_url( trailingslashit( $base ) . $block['id'] ),
			),
			'collection' => array(
				'href'   => rest_url( $base ),
			),
		);

		$active_blocks = get_option( 'entrepot_active_blocks', array() );

		if ( in_array( $block['id'], $active_blocks, true ) ) {
			$links['action'] = array(
				'href'       => add_query_arg( array(
					'page'     => 'entrepot-blocks',
					'_wpnonce' => wp_create_nonce( 'deactivate-block_' . $block['id'] ),
					'action'   => 'deactivate',
					'block'    => $block['id'],
				), network_admin_url( 'admin.php' ) ),
				'embeddable' => true,
				'title'      => __( 'Désactiver', 'entrepot' ),
			);
		} else {
			$links['action'] = array(
				'href'       => add_query_arg( array(
					'page'     => 'entrepot-blocks',
					'_wpnonce' => wp_create_nonce( 'activate-block_' . $block['id'] ),
					'action'   => 'activate',
					'block'    => $block['id'],
				), network_admin_url( 'admin.php' ) ),
				'embeddable' => true,
				'title'      => __( 'Activer', 'entrepot' ),
			);
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

		$schema     = $this->get_item_schema();
        $properties = wp_list_filter( $schema['properties'], array( 'type' => 'string' ) );

		// Sanitize Block data to translate.
		foreach ( $properties as $property => $params ) {
            $value = $block[ $property ];

			if ( 'description' === $property ) {
				$value = wp_trim_words( $value, 15 );
			}

			$block[ $property ] = strip_tags( $value );
        }

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$block   = $this->filter_response_by_context( $block, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $block );

		$links = $this->prepare_links( $block );
		$response->add_links( $links );

		return $response;
	}

	/**
	 * Retrieves all of the installed block types or the Rest additionnal shema.
	 *
	 * @since 1.5.0
	 *
	 * @param  boolean $schema True to get the additionnal schema.
	 * @return array array of registered options.
	 */
	protected function get_installed_blocks( $schema = false ) {
		$rest_blocks = array();

		if ( ! $this->blocks ) {
			$this->blocks = entrepot_get_blocks();
        }

		foreach ( $this->blocks as $dir => $data ) {
			if ( $schema ) {
				$rest_data = array();

				$default_schema = array(
					'type'        => 'string',
					'description' => '',
					'context'     => array( 'view', 'edit', 'embed' ),
				);

			} else {
				$rest_data = array(
                    'id'   => $data->id,
                    'slug' => wp_basename( $dir ),
                );
			}

            $vars = get_object_vars( $data );
            $keys = array_keys( $vars );

			foreach ( array_map( 'sanitize_key', $keys ) as $key_id => $prop ) {
				if ( $schema && ! isset( $rest_blocks[ $prop ] ) ) {
					$rest_data['id']     = $prop;
					$rest_data['schema'] = wp_parse_args( array(
						'description' => $keys[ $key_id ],
						'type'        => is_bool( $data->{$keys[ $key_id ]} ) ? 'boolean' : 'string',
					), $default_schema );

					$rest_blocks[ $rest_data['id'] ] = $rest_args;

				} else {
                    $rest_data[ $prop ] = $data->{$keys[ $key_id ]};
				}
            }

            if ( isset( $rest_data['slug'] ) ) {
                $repository = entrepot_get_repositories( $rest_data['slug'], 'blocks' );
                $locale     = get_user_locale();
                if ( ! $locale ) {
                    $locale = 'en_US';
                }

                if ( isset( $repository->description ) ) {
                    $rest_data['description'] = $repository->description->en_US;

                    if ( isset( $repository->description->{$locale} ) ) {
                        $rest_data['description'] = $repository->description->{$locale};
                    }
                }

                if ( isset( $repository->icon ) ) {
					$rest_data['icon'] = $repository->icon;
                }
            }

			if ( ! $schema ) {
				if ( empty( $rest_data['icon'] ) ) {
					$rest_data['icon'] = esc_url( trailingslashit( entrepot_assets_url() ) . 'block.svg' );
				}

				$rest_blocks[ $rest_data['id'] ] = $rest_data;
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
		$blocks = $this->get_installed_blocks( true );

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'blocks',
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'description' => __( 'Un identifiant alphanumérique unique par rapport au répertoire et au fichier principal de l\'extension.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'slug'            => array(
					'description' => __( 'Un identifiant alphanumérique unique par rapport à la terminaison de l\'extension.', 'entrepot' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		);

		foreach ( $blocks as $property_name => $property ) {
			$schema['properties'][ $property_name ] = $property['schema'];
		}

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
			'description' => __( 'Limit response to blocks corresponding to the current tab.' ),
			'type'        => 'string',
			'default'     => 'installed',
			'enum'        => array( 'installed', 'available' ),
		);

		return $query_params;
	}
}
