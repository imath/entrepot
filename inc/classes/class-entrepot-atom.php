<?php
/**
 * Entrepôt's Atom Parser class.
 *
 * @package Entrepôt\inc\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AtomParser') ) {
	require_once ABSPATH . WPINC . '/atomlib.php';
}

/**
 * Entrepôt's Atom Parser class.
 *
 * @since  1.0.0
 */
class Entrepot_Atom extends AtomParser {
	/**
	 * Whether the fopen method should be used.
	 *
	 * @var boolean
	 */
	public $use_fopen = false;

	/**
	 * Class Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $feed The URL or path to the Atom feed.
	 */
	public function __construct( $feed = '' ) {
		parent::__construct();

		if ( defined( 'ENTREPOT_ATOM_USE_FOPEN' ) ) {
			$this->use_fopen = ENTREPOT_ATOM_USE_FOPEN;
		}

		if ( $feed ) {
			$this->FILE = $feed;
			$this->parse();
		}
	}

	/**
	 * Override the Parse function to use the WP HTTP API for external requests.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True on success, False otherwise.
	 */
	public function parse() {
			set_error_handler( array( &$this, 'error_handler' ) );

			array_unshift( $this->ns_contexts, array() );

			if ( ! function_exists( 'xml_parser_create_ns' ) ) {
				/* translators: do not translate this string, WordPress already handles it. */
				trigger_error( __( "PHP's XML extension is not available. Please contact your hosting provider to enable PHP's XML extension.", "default" ) );
				return false;
			}

			$parser = xml_parser_create_ns();
			xml_set_object( $parser, $this );
			xml_set_element_handler( $parser, 'start_element', 'end_element' );

			xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
			xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE,   0 );

			xml_set_character_data_handler( $parser, 'cdata' );
			xml_set_default_handler( $parser, '_default' );

			xml_set_start_namespace_decl_handler( $parser, 'start_ns' );
			xml_set_end_namespace_decl_handler( $parser,   'end_ns'   );

			$this->content = '';

			$ret = true;

			$feed = get_site_transient( 'entrepot_feed_' . md5( $this->FILE ) );

			if ( ! $feed ) {
				// Define ENTREPOT_ATOM_USE_FOPEN to true to force fopen method.
				if ( $this->use_fopen || false !== strpos( $this->FILE, rtrim( ABSPATH, '/\\' ) ) ) {
					$fp = fopen( $this->FILE, 'r' );
					while ( $data = fread( $fp, 4096 ) ) {
							if ( $this->debug ) {
								$this->content .= $data;
							}

							if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) {
									/* translators: do not translate this string, WordPress already handles it. */
									trigger_error( sprintf( __( 'XML Error: %1$s at line %2$s', 'default' )."\n",
											xml_error_string( xml_get_error_code( $parser ) ),
											xml_get_current_line_number( $parser )
									) );
									$ret = false;
									break;
							}
					}
					fclose( $fp );

				// External requests will use the WP HTTP API.
				} else {
					$options = array(
						'timeout' => 60,
						'user-agent'	=> 'Entrepôt/WordPress-Plugin-Updater; ' . get_bloginfo( 'url' ),
					);

					$external_request = wp_remote_get( $this->FILE, $options );
					$data             = wp_remote_retrieve_body( $external_request );

					if ( $this->debug ) {
						$this->content = $data;
					}

					if ( is_wp_error( $external_request ) ) {
						trigger_error( $external_request->get_error_message() );
						$ret = false;
					}

					if ( 200 !== (int) wp_remote_retrieve_response_code( $external_request ) ) {
						trigger_error( sprintf( __( 'Erreur de transport - le code de réponse HTTP n\'est pas 200 (%s)', 'entrepot' ),
							wp_remote_retrieve_response_code( $external_request )
						) );
						$ret = false;
					}

					if ( false === $ret ) {
						return;
					}

					if ( ! xml_parse( $parser, $data, true ) ) {
							/* translators: do not translate this string, WordPress already handles it. */
							trigger_error( __( 'Erreur XML durant l\'analyse.', 'entrepot' ) );
							$ret = false;
					}
				}

				if ( ! empty( $this->feed ) && ! empty( $this->feed->entries ) ) {
					set_site_transient( 'entrepot_feed_' . md5( $this->FILE ), $this->feed, 2 * HOUR_IN_SECONDS );
				}

			// Use the cached feed.
			} else {
				$this->feed = $feed;
			}

			xml_parser_free( $parser );

			restore_error_handler();

			return $ret;
	}
}
