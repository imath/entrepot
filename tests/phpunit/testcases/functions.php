<?php
/**
 * Functions tests.
 */

/**
 * @group functions
 */
class Plugin_Repositories_Tests extends WP_UnitTestCase {

	public function repositories_dir() {
		return PR_TESTING_ASSETS;
	}

	public function test_plugin_repositories_get_plugin_latest_stable_release() {
		$stable = PR_TESTING_ASSETS . '/releases-stable.atom';
		$release = plugin_repositories_get_plugin_latest_stable_release( $stable );

		$this->assertTrue( is_object( $release ) );
	}
}
