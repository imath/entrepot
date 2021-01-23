<?php
/**
 * Entrepôt's REST Plugins controller.
 *
 * @package Entrepôt\inc\classes
 *
 * @since 1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_REST_Plugins_Controller', false ) ) {
	require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-controller.php';
	require ABSPATH . WPINC . '/rest-api/endpoints/class-wp-plugins-controller.php';
}

/**
 * Class used to manage a Entrepôt registered plugins via the REST API.
 *
 * @since 1.5.0
 *
 * @see WP_REST_Plugins_Controller
 */
class Entrepot_REST_Plugins_Controller extends WP_REST_Plugins_Controller {
	/**
	 * Plugins controller constructor.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {
		$this->namespace = 'entrepot/v1';
		$this->rest_base = 'plugins';
	}

	/**
	 * Registers the routes for the Entrepôt registered plugins controller.
	 *
	 * @since 1.6.0
	 */
	public function register_routes() {
		parent::register_routes();
	}

	/**
	 * Returns an invalid method (not implemented) error.
	 *
	 * @param $method The name of the method not yet implemented.
	 * @return WP_Error a WP_Error object.
	 */
	protected function not_implemented( $method ) {
		return new WP_Error(
			'invalid-method',
			/* translators: %s: Method name. */
			sprintf( __( "La méthode '%s' n’est pas encore implémentée.", 'entrepot' ), $method ),
			array( 'status' => 405 )
		);
	}

	/**
	 * Retrieves a collection of Entrepôt registered plugins.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$github_repositories = array();
		$github_url          = add_query_arg(
			array(
				'q' => 'topic:entrepot-registered+topic:wordpress-plugin',
			),
			'https://api.github.com/search/repositories'
		);

		$github_response = entrepot_remote_request_get( $github_url );
		$response_code   = wp_remote_retrieve_response_code( $github_response );

		if ( 200 !== $response_code ) {
			return new WP_Error(
				'api-error',
				/* translators: %d: Numeric HTTP status code, e.g. 400, 403, 500, 504, etc. */
				sprintf( __( 'Erreur de l’API de GitHub API : code (%d).', 'entrepot' ), $response_code )
			);
		}

		// Gets GitHub API headers & body.
		$github_response_headers = wp_remote_retrieve_headers( $github_response );
		$github_response_body    = json_decode( wp_remote_retrieve_body( $github_response ), true );

		if ( isset( $github_response_body['items'] ) && $github_response_body['items'] ) {
			foreach ( $github_response_body['items'] as $github_repository ) {
				if ( ! isset( $github_repository['full_name'] ) || ! $github_repository['full_name'] ) {
					continue;
				}

				$full_name                         = str_replace( '/', '_', $github_repository['full_name'] );
				$github_repositories[ $full_name ] = $github_repository;

				if ( isset ( $github_repository['owner']['avatar_url'] ) ) {
					$github_repositories[ $full_name ]['owner_avatar_url'] = $github_repository['owner']['avatar_url'];
				}
			}
		}

		// Merge GitHub plugins with registered ones.
		$repositories = entrepot_get_plugin_repositories_list( $github_repositories );

		/**
		 * Filter here to edit the returned plugin repositories for the get_items() endpoint.
		 *
		 * @since 1.6.0
		 *
		 * @param array $repositories The list of plugin repository objects.
		 * @param array $github_repositories The list of plugin repository arrays.
		 */
		$repositories       = apply_filters( 'entrepot_rest_get_plugins', $repositories, $github_repositories );
		$repositories_count = count( $repositories );
		$response           = rest_ensure_response( $repositories );

		// Add headers.
		$response->header( 'X-WP-Total', (int) $repositories_count );
		$response->header( 'X-WP-TotalPages', (int) $repositories_count );

		if ( is_object( $github_response_headers ) ) {
			$response->header( 'X-GH-ratelimit-limit', (int) $github_response_headers['x-ratelimit-limit'] );
			$response->header( 'X-GH-ratelimit-remaining', (int) $github_response_headers['x-ratelimit-remaining'] );
			$response->header( 'X-GH-ratelimit-reset', (int) $github_response_headers['x-ratelimit-reset'] );
			$response->header( 'X-GH-ratelimit-used', (int) $github_response_headers['x-ratelimit-used'] );
		}

		return $response;
	}

	/**
	 * Retrieves one plugin from the site.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		return $this->not_implemented( __METHOD__ );
	}

	/**
	 * Uploads a plugin and optionally activates it.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		return $this->not_implemented( __METHOD__ );
	}

	/**
	 * Updates one plugin.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		return $this->not_implemented( __METHOD__ );
	}

	/**
	 * Deletes one plugin from the site.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		return $this->not_implemented( __METHOD__ );
	}
}
