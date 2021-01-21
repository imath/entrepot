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
		$github_url = add_query_arg(
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

		/**
		 * @todo check rate limits.
		 * $response_headers = wp_remote_retrieve_headers( $response );
		 */

		$repositories = json_decode( wp_remote_retrieve_body( $github_response ), true );

		return rest_ensure_response( $repositories );
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
