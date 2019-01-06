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

	public function block_repositories_dir() {
		return trailingslashit( $this->blocks_dir() );
	}

	/**
	 * @group blocks
	 */
	public function test_entrepot_blocks_dir() {
		add_filter( 'entrepot_installed_blocks_dir', array( $this, 'blocks_dir' ) );

		wp_cache_delete( 'blocks', 'entrepot' );
		$blocks = entrepot_get_blocks();
		$this->assertTrue( 1 === count( $blocks ) );

		$block = reset( $blocks );
		$this->assertTrue( 'random-block' === wp_basename( $block->path ) );

		remove_filter( 'entrepot_installed_blocks_dir', array( $this, 'blocks_dir' ) );
	}

	/**
	 * @group blocks
	 */
	public function test_entrepot_blocks_dir_random_block() {
		add_filter( 'entrepot_installed_blocks_dir', array( $this, 'blocks_dir' ) );

		wp_cache_delete( 'blocks', 'entrepot' );
		$block = entrepot_get_blocks( 'random-block' );

		$this->assertTrue( 'random-block' === wp_basename( $block->path ) );

		remove_filter( 'entrepot_installed_blocks_dir', array( $this, 'blocks_dir' ) );
	}

	/**
	 * @group blocks
	 */
	public function test_entrepot_blocks_dir_random_folder() {
		add_filter( 'entrepot_installed_blocks_dir', array( $this, 'blocks_dir' ) );

		wp_cache_delete( 'blocks', 'entrepot' );
		$block = entrepot_get_blocks( 'random-folder' );

		$this->assertEmpty( $block );

		remove_filter( 'entrepot_installed_blocks_dir', array( $this, 'blocks_dir' ) );
	}

	/**
	 * @group update
	 * @group blocks
	 */
	public function test_entrepot_blocks_get_updates() {
		$stable = PR_TESTING_ASSETS . '/blocks/releases.atom';

		add_filter( 'entrepot_repositories_dir', array( $this, 'block_repositories_dir' ) );
		add_filter( 'entrepot_installed_blocks_dir', array( $this, 'blocks_dir' ) );

		$updates = entrepot_blocks_get_updates();

		remove_filter( 'entrepot_repositories_dir', array( $this, 'block_repositories_dir' ) );
		remove_filter( 'entrepot_installed_blocks_dir', array( $this, 'blocks_dir' ) );

		$release = $updates['imath/random-block'];

		$this->assertTrue( $release->is_update );
		$this->assertTrue( '2.0.0' === $release->new_version );
	}
}
