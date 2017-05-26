<?php
/**
 * Admin tests.
 */

/**
 * @group admin
 */
class Plugin_Repositories_Admin_Tests extends WP_UnitTestCase {

	public function repositories_dir() {
		return PR_TESTING_ASSETS;
	}

	public function test_plugin_repositories_get_installed_repositories() {
		set_current_screen( 'dashboard' );

		$plugin_data = get_plugin_data( PR_TESTING_ASSETS . '/test-plugin.php', true, false );

		$this->assertTrue( isset( $plugin_data['GitHub Plugin URI'] ) );

		set_current_screen( 'front' );
	}

	/**
	 * @group updates
	 */
	public function test_plugin_repositories_update_plugin_repositories() {
		add_filter( 'plugin_repositories_plugins_dir', array( $this, 'repositories_dir' ) );

		wp_update_plugins();

		remove_filter( 'plugin_repositories_plugins_dir', array( $this, 'repositories_dir' ) );

		$updates = get_site_transient( 'update_plugins' )->response;
		$this->assertNotEmpty( $updates['plugin-repositories/plugin-repositories.php']->package );
	}
}
