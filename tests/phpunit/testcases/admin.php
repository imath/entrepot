<?php
/**
 * Admin tests.
 */

/**
 * @group admin
 */
class galerie_Admin_Tests extends WP_UnitTestCase {

	public function setUp() {
		require_once( galerie()->inc_dir . 'admin.php' );

		parent::setUp();
	}

	public function repositories_dir() {
		return PR_TESTING_ASSETS;
	}

	public function test_galerie_get_installed_repositories() {
		set_current_screen( 'dashboard' );

		$plugin_data = get_plugin_data( PR_TESTING_ASSETS . '/test-plugin.php', true, false );

		$this->assertTrue( isset( $plugin_data['GitHub Plugin URI'] ) );

		set_current_screen( 'front' );
	}

	/**
	 * @group updates
	 */
	public function test_galerie_update_repositories() {
		add_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		wp_update_plugins();

		remove_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$updates = get_site_transient( 'update_plugins' )->response;
		$this->assertNotEmpty( $updates['galerie/galerie.php']->package );
		$this->assertTrue( $updates['galerie/galerie.php']->is_update );
	}

	public function test_galerie_admin_get_repositories_list() {
		set_current_screen( 'dashboard' );

		$repositories = galerie_admin_get_repositories_list();

		foreach ( $repositories as $repository ) {
			$this->assertNotEmpty( $repository->slug );
			$this->assertNotEmpty( $repository->author );

			if ( 'galerie' !== $repository->slug ) {
				$this->assertNotEmpty( $repository->releases );
				$this->assertTrue( rtrim( $repository->releases, '/' ) === 'https://github.com/' . $repository->author . '/' . $repository->slug . '/releases' );
			}

			$this->assertTrue( file_exists( galerie_plugins_dir() . $repository->slug . '.json' ) );
			$this->assertNotEmpty( $repository->description->en_US );
			$this->assertNotEmpty( $repository->README );
		}

		set_current_screen( 'front' );
	}

	/**
	 * @group cache
	 */
	public function test_galerie_admin_updater() {
		set_current_screen( 'dashboard' );

		$db_version = galerie_db_version();

		$repositories = galerie_get_repositories();
		$this->assertSame( wp_cache_get('repositories', 'galerie' ), $repositories );

		do_action( 'galerie_admin_init' );

		// There was an upgrade, cache should be reset.
		$this->assertFalse( wp_cache_get('repositories', 'galerie' ) );

		$repositories = galerie_get_repositories();

		do_action( 'galerie_admin_init' );

		// There was no upgrade, cache should not be reset.
		$this->assertSame( wp_cache_get('repositories', 'galerie' ), $repositories );

		// Restore
		set_current_screen( 'front' );
		update_network_option( 0, '_galerie_version', $db_version );
	}
}
