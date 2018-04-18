<?php
/**
 * Admin tests.
 */

/**
 * @group admin
 */
class entrepot_Admin_Tests extends WP_UnitTestCase {

	public function setUp() {
		require_once entrepot()->inc_dir . 'admin.php';

		parent::setUp();
	}

	public function repositories_dir() {
		return PR_TESTING_ASSETS;
	}

	public function test_entrepot_get_installed_repositories() {
		set_current_screen( 'dashboard' );

		$plugin_data = get_plugin_data( PR_TESTING_ASSETS . '/test-plugin.php', true, false );

		$this->assertTrue( isset( $plugin_data['GitHub Plugin URI'] ) );

		set_current_screen( 'front' );
	}

	/**
	 * @group updates
	 */
	public function test_entrepot_update_repositories() {
		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		wp_update_plugins();

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$updates = get_site_transient( 'update_plugins' )->response;
		$this->assertNotEmpty( $updates['entrepot/entrepot.php']->package );
		$this->assertTrue( $updates['entrepot/entrepot.php']->is_update );
	}

	public function test_entrepot_admin_get_repositories_list() {
		set_current_screen( 'dashboard' );

		$repositories = entrepot_admin_get_repositories_list();
		$slugs        = array();

		foreach ( $repositories as $repository ) {
			$this->assertNotEmpty( $repository->slug, 'The slug property of the plugin should be set.' );
			$this->assertNotEmpty( $repository->author, 'The author property of the plugin should be set.' );

			if ( 'entrepot' !== $repository->slug ) {
				$this->assertNotEmpty( $repository->releases, 'The releases URL property of the plugin should be set.' );
				$this->assertTrue(
					rtrim( $repository->releases, '/' ) === 'https://github.com/' . $repository->author . '/' . $repository->slug . '/releases',
					'The releases URL property should have this form https://github.com/{author}/{slug}/releases.'
				);
			}

			$slugs[] = $repository->slug;
			$this->assertTrue( file_exists( entrepot_plugins_dir() . $repository->slug . '.json' ), 'The slug property should be used as the json file name' );
			$this->assertNotEmpty( $repository->description->en_US, 'An american (en_US) description should be provided for the plugin.' );
			$this->assertNotEmpty( $repository->README, 'The README property of the plugin should be set.' );
		}

		$this->assertTrue( count( $repositories ) === count( array_unique( $slugs ) ), 'Plugin slugs should be unique.' );

		set_current_screen( 'front' );
	}

	/**
	 * @group cache
	 */
	public function test_entrepot_admin_updater() {
		set_current_screen( 'dashboard' );

		$db_version = entrepot_db_version();

		$repositories = entrepot_get_repositories();
		$this->assertSame( wp_cache_get('repositories', 'entrepot' ), $repositories );

		do_action( 'entrepot_admin_init' );

		// There was an upgrade, cache should be reset.
		$this->assertFalse( wp_cache_get('repositories', 'entrepot' ) );

		$repositories = entrepot_get_repositories();

		do_action( 'entrepot_admin_init' );

		// There was no upgrade, cache should not be reset.
		$this->assertSame( wp_cache_get('repositories', 'entrepot' ), $repositories );

		// Restore
		set_current_screen( 'front' );
		update_network_option( 0, '_entrepot_version', $db_version );
	}

	public function test_entrepot_all_installed_repositories_list() {
		set_current_screen( 'dashboard' );

		$plugins = apply_filters( 'all_plugins', get_plugins() );
		$entrepot = wp_list_filter( $plugins, array( 'slug' => 'entrepot' ) );

		$this->assertTrue( 1 === count( $entrepot ) );

		set_current_screen( 'front' );
	}
}
