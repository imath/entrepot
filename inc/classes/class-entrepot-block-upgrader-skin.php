<?php
/**
 * Entrepôt's Block Types Upgrader skin.
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
 * Block Upgrader Skin for Entrepôt Block Upgrader.
 *
 * @since 1.5.0
 *
 * @see WP_Upgrader_Skin
 */
class Entrepot_Block_Upgrader_Skin extends WP_Upgrader_Skin {
	public $block_id = '';
	public $block_active = false;

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

        $active_blocks      = (array) get_site_option( 'entrepot_active_blocks', array() );
        $this->block_id     = $args['block'];
		$this->block_active = $this->block_id && in_array( $this->block_id, $active_blocks, true );

        $this->api  = array();
        if ( isset( $args['api'] ) ) {
            $this->api = $args['api'];
        }

		parent::__construct( $args );
	}

	/**
     * Action links to display once the block has been upgraded.
     *
     * @since 1.5.0
	 */
	public function after() {
        // Url to activate the Block
        $activate_url = wp_nonce_url( add_query_arg( array(
            'page'     => 'entrepot-blocks',
            'action'   => 'activate',
            'block'    => $this->block_id,
        ), network_admin_url( 'admin.php' ) ), 'activate-block_' . $this->block_id );

        // Reactivate the block if needed, making sure the update does not contain errors.
        if ( $this->block_active && ! is_wp_error( $this->result ) && $this->block_active ) {
            printf(
                '<iframe title="%1$s" style="border:0;overflow:hidden" width="100%" height="170" src="%2$s"></iframe>',
                esc_attr__( 'Progression de la mise à jour du bloc', 'entrepot' ),
                esc_url_raw( $activate_url )
            );
		}

		$update_actions =  array(
            'activate' => sprintf( '<a class="button button-primary" href="%1$s" target="_parent">%2$s</a>',
                $activate_url,
                esc_html__( 'Activer le bloc', 'entrepot' )
            ),
            'blocks_page' => sprintf( '<a href="%1$s" target="_parent">%2$s</a>',
                add_query_arg( array(
                    'page'     => 'entrepot-blocks',
                ), network_admin_url( 'admin.php' ) ),
                esc_html__( 'Revenir à l’administration des blocs', 'entrepot' )
            ),
        );

        if ( $this->block_active || ! $this->result || is_wp_error( $this->result ) || ! current_user_can( 'activate_entrepot_blocks' ) ) {
			unset( $update_actions['activate'] );
        }

		/**
		 * Filters the list of action links available following a block upgrade.
		 *
		 * @since 1.5.0
		 *
		 * @param array  $update_actions Array of Block action links.
		 * @param object $api            Object containing Entrepôt API block data.
		 * @param string $block_id       The Block type ID (author name / block name)
		 */
		$update_actions = apply_filters( 'entrepot_update_block_complete_actions', $update_actions, $this->api, $this->block_id );

		if ( ! $update_actions ) {
			return;
        }

        $this->feedback( implode( ' ', (array) $update_actions ) );
	}
}
