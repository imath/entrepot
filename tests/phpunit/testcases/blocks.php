<?php
/**
 * Blocks Functions tests.
 */

/**
 * @requires function render_block
 * @group blocks
 */
class entrepot_Blocks_Tests extends WP_UnitTestCase {

	public function blocks_dir() {
		return PR_TESTING_ASSETS . '/blocks';
	}

	/**
	 * @group blocks
	 */
	public function test_entrepot_blocks_dir() {
		add_filter( 'entrepot_blocks_dir', array( $this, 'blocks_dir' ) );

		wp_cache_delete( 'blocks', 'entrepot' );
		$blocks = entrepot_get_blocks();
		$this->assertTrue( 1 === count( $blocks ) );

		$block = reset( $blocks );
		$this->assertTrue( 'random-block' === wp_basename( $block->path ) );

		remove_filter( 'entrepot_blocks_dir', array( $this, 'blocks_dir' ) );
	}

	/**
	 * @group blocks
	 */
	public function test_entrepot_blocks_dir_random_block() {
		add_filter( 'entrepot_blocks_dir', array( $this, 'blocks_dir' ) );

		wp_cache_delete( 'blocks', 'entrepot' );
		$block = entrepot_get_blocks( 'random-block' );

		$this->assertTrue( 'random-block' === wp_basename( $block->path ) );

		remove_filter( 'entrepot_blocks_dir', array( $this, 'blocks_dir' ) );
	}

	/**
	 * @group blocks
	 */
	public function test_entrepot_blocks_dir_random_folder() {
		add_filter( 'entrepot_blocks_dir', array( $this, 'blocks_dir' ) );

		wp_cache_delete( 'blocks', 'entrepot' );
		$block = entrepot_get_blocks( 'random-folder' );

		$this->assertEmpty( $block );

		remove_filter( 'entrepot_blocks_dir', array( $this, 'blocks_dir' ) );
	}
}
