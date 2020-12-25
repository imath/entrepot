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
		require_once entrepot()->inc_dir . 'admin-hooks.php';

		parent::setUp();
	}

	public function repositories_dir() {
		return PR_TESTING_ASSETS . '/';
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
		add_filter( 'entrepot_repositories_dir', array( $this, 'repositories_dir' ) );

		wp_update_plugins();

		remove_filter( 'entrepot_repositories_dir', array( $this, 'repositories_dir' ) );

		$updates = get_site_transient( 'update_plugins' )->response;
		$this->assertNotEmpty( $updates['entrepot/entrepot.php']->package );
		$this->assertTrue( $updates['entrepot/entrepot.php']->is_update );
	}

	/**
	 * @group plugins
	 */
	public function test_entrepot_admin_get_plugin_repositories_list() {
		set_current_screen( 'dashboard' );

		$repositories = entrepot_admin_get_plugin_repositories_list();
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
			$this->assertTrue( file_exists( entrepot_repositories_dir() . $repository->slug . '.json' ), 'The slug property should be used as the json file name' );
			$this->assertNotEmpty( $repository->description->en_US, 'An american (en_US) description should be provided for the plugin.' );
			$this->assertNotEmpty( $repository->README, 'The README property of the plugin should be set.' );
		}

		$this->assertTrue( count( $repositories ) === count( array_unique( $slugs ) ), 'Plugin slugs should be unique.' );

		set_current_screen( 'front' );
	}

	/**
	 * @group themes
	 */
	public function test_entrepot_admin_get_theme_repositories_list() {
		set_current_screen( 'dashboard' );

		$repositories = entrepot_admin_get_theme_repositories_list();
		$slugs        = array();

		foreach ( $repositories as $repository ) {
			$this->assertNotEmpty( $repository->name, 'The name property of the theme should be set.' );
			$this->assertNotEmpty( $repository->slug, 'The slug property of the theme should be set.' );
			$this->assertNotEmpty( $repository->author, 'The author property of the theme should be set.' );
			$this->assertNotEmpty( $repository->screenshot, 'The screenshot property of the theme should be set.' );
			$this->assertNotEmpty( $repository->releases, 'The releases URL property of the theme should be set.' );
			$this->assertTrue(
				rtrim( $repository->releases, '/' ) === 'https://github.com/' . $repository->authorAndUri . '/' . $repository->slug . '/releases',
				'The releases URL property should have this form https://github.com/{author}/{slug}/releases.'
			);

			$slugs[] = $repository->slug;

			$this->assertTrue( file_exists( entrepot_repositories_dir( 'themes' ) . $repository->slug . '.json' ), 'The slug property should be used as the json file name' );
			$this->assertNotEmpty( $repository->descriptions->en_US, 'An american (en_US) description should be provided for the theme.' );
			$this->assertNotEmpty( $repository->README, 'The README property of the theme should be set.' );
		}

		$this->assertTrue( count( $repositories ) === count( array_unique( $slugs ) ), 'Theme slugs should be unique.' );

		set_current_screen( 'front' );
	}

	public function use_test_theme( $repositories = array() ) {
		$json = file_get_contents( PR_TESTING_ASSETS . '/test-theme.json' );
		return array( json_decode( $json ) );
	}

	/**
	 * @group themes
	 * @group requirements
	 */
	public function test_entrepot_admin_get_theme_requirements() {
		set_current_screen( 'dashboard' );

		add_filter( '_entrepot_get_repositories', array( $this, 'use_test_theme' ), 10, 1 );

		$repositories = entrepot_admin_get_theme_repositories_list();

		remove_filter( '_entrepot_get_repositories', array( $this, 'use_test_theme' ), 10, 1 );

		$repository = reset( $repositories );

		$this->assertObjectHasAttribute( 'requires', $repository );
		$this->assertObjectHasAttribute( 'requires_php', $repository );
		$this->assertObjectHasAttribute( 'compatibleWP', $repository );
		$this->assertObjectHasAttribute( 'compatiblePHP', $repository );

		$this->assertEquals( '5.5', $repository->requires );
		$this->assertEquals( '7.0', $repository->requires_php );

		set_current_screen( 'front' );
	}

	public function test_entrepot_all_installed_repositories_list() {
		set_current_screen( 'dashboard' );

		$plugins = apply_filters( 'all_plugins', get_plugins() );
		$entrepot = wp_list_filter( $plugins, array( 'slug' => 'entrepot' ) );

		$this->assertTrue( 1 === count( $entrepot ) );

		set_current_screen( 'front' );
	}
}
