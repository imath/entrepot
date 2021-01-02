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
					'timeout'    => 60,
					'user-agent' => 'Entrepôt/WordPress-Plugin-Updater; ' . get_bloginfo( 'url' ),
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

	/**
	 * Override parent's method to prevent a fatal error in PHP 8.
	 *
	 * @since 1.5.5
	 *
	 * @param XmlParser $parser The XML parser object.
	 * @param string    $name   The element name.
	 */
	public function end_element( $parser, $name ) {
		$name_parts = explode( ':', $name );
		$tag        = array_pop( $name_parts );

		$ccount = count( $this->in_content );

		// if we are *in* content, then let's proceed to serialize it.
		if ( ! empty( $this->in_content ) ) {
			/*
			 * if we are ending the original content element.
			 * then let's finalize the content.
			 */
			if ( isset( $this->in_content[0][0], $this->in_content[0][1] ) && $this->in_content[0][0] === $tag && $this->in_content[0][1] === $this->depth ) {
				$origtype = '';
				if ( isset( $this->in_content[0][2] ) ) {
					$origtype = $this->in_content[0][2];
				}

				array_shift( $this->in_content );
				$newcontent = array();

				foreach( $this->in_content as $c ) {
					if ( is_array( $c ) && count( $c ) === 3 ) {
						array_push( $newcontent, $c[2] );
					} else {
						if ( $this->is_xhtml || $this->is_text ) {
							array_push( $newcontent, $this->xml_escape( $c ) );
						} else {
							array_push( $newcontent, $c );
						}
					}
				}

				if ( in_array( $tag, $this->ATOM_CONTENT_ELEMENTS ) ) {
					$this->current->$tag = array( $origtype, join( '', $newcontent ) );
				} else {
					$this->current->$tag = join( '', $newcontent );
				}

				$this->in_content = array();
			} elseif ( isset( $this->in_content[ $ccount-1 ][0], $this->in_content[ $ccount-1 ][1], $this->in_content[ $ccount-1 ][2] ) && $this->in_content[ $ccount-1 ][0] === $tag && $this->in_content[ $ccount-1 ][1] === $this->depth ) {
				$this->in_content[$ccount-1][2] = substr( $this->in_content[ $ccount-1 ][2], 0, -1 ) . "/>";
			} else {
				// else, just finalize the current element's content.
				$endtag = $this->ns_to_prefix( $name );
				array_push( $this->in_content, array( $tag, $this->depth, "</$endtag[1]>" ) );
			}
		}

		array_shift( $this->ns_contexts );

		$this->depth--;

		if ( $name == ( $this->NS . ':entry' ) ) {
			array_push( $this->feed->entries, $this->current );
			$this->current = null;
		}

		$this->_p( "end_element('$name')" );
	}
}
