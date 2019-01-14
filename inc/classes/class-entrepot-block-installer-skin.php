<?php
/**
 * Entrepôt's Block Types Installer skin.
 *
 * @package Entrepôt\inc\classes
 *
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Upgrader_Skin') ) {
	require ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
}

/**
 * Block Installer Skin for Entrepôt Block Installer.
 *
 * @since 1.5.0
 *
 * @see WP_Upgrader_Skin
 */
class Entrepot_Block_Installer_Skin extends WP_Upgrader_Skin {
	public $api;
	public $type;

	/**
	 * Constructor.
     *
     * @since 1.5.0
     *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
            'type'  => 'web',
            'url'   => '',
            'block' => '',
            'nonce' => '',
            'title' => '',
        ) );

        $this->block_id = $args['block'];
		$this->type     = $args['type'];

        $this->api  = array();
        if ( isset( $args['api'] ) ) {
            $this->api = $args['api'];
        }

		parent::__construct( $args );
	}

	/**
     * Feedback to display once the block has been installed.
     *
     * @since 1.5.0
	 */
	public function before() {
		if ( ! $this->api ) {
            return;
        }

        $this->upgrader->strings['process_success'] = sprintf(
            esc_html__( 'Le bloc %1$s %2$s a été installé avec succès.', 'entrepot' ),
            '<strong>' . $this->api->name,
            $this->api->version . '</strong>'
        );
	}

	/**
     * Action links to display once the block has been installed.
     *
     * @since 1.5.0
	 */
	public function after() {
		$install_actions = array(
            'activate' => sprintf( '<a class="button button-primary" href="%1$s" target="_parent">%2$s</a>',
                wp_nonce_url( add_query_arg( array(
                    'page'     => 'entrepot-blocks',
                    'action'   => 'activate',
                    'block'    => $this->block_id,
                ), network_admin_url( 'admin.php' ) ), 'activate-block_' . $this->block_id ),
                esc_html__( 'Activer le bloc', 'entrepot' )
            ),
            'blocks_page' => sprintf( '<a href="%1$s" target="_parent">%2$s</a>',
                add_query_arg( array(
                    'page'     => 'entrepot-blocks',
                ), network_admin_url( 'admin.php' ) ),
                esc_html__( 'Revenir à l’administration des blocs', 'entrepot' )
            ),
        );

		if ( ! $this->result || is_wp_error( $this->result ) || ! current_user_can( 'activate_entrepot_blocks' ) ) {
			unset( $install_actions['activate'] );
		}

		/**
		 * Filters the list of action links available following a block installation.
		 *
		 * @since 1.5.0
		 *
		 * @param array  $install_actions Array of Block action links.
		 * @param object $api             Object containing Entrepôt API block data.
		 * @param string $block_id        The Block type ID (author name / block name)
		 */
		$install_actions = apply_filters( 'entrepot_install_block_complete_actions', $install_actions, $this->api, $this->block_id );

		if ( ! $install_actions ) {
			return;
        }

        $this->feedback( implode( ' ', (array) $install_actions ) );
	}
}
