<?php
/**
 * Entrepôt Blocks functions.
 *
 * @package Entrepôt\inc
 *
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Deactivate a block.
 *
 * @since 1.5.0
 *
 * @param string $block_type_id The block type ID.
 * @return boolean True.
 */
function entrepot_deactivate_block( $block_type_id = '' ) {
	$active_blocks = (array) get_option( 'entrepot_active_blocks', array() );
	$block_index   = array_search( $block_type_id, $active_blocks, true );

	if ( false !== $block_index ) {
		unset( $active_blocks[ $block_index ] );
		update_option( 'entrepot_active_blocks', array_unique( $active_blocks ) );
	}

	return true;
}
