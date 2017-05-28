<?php
/**
 * Functions tests.
 */

/**
 * @group functions
 */
class galerie_Functions_Tests extends WP_UnitTestCase {

	public function repositories_dir() {
		return PR_TESTING_ASSETS;
	}

	public function test_galerie_get_plugin_latest_stable_release() {
		$stable = PR_TESTING_ASSETS . '/releases-stable.atom';
		$release = galerie_get_plugin_latest_stable_release( $stable );

		$this->assertTrue( is_object( $release ) );
	}

	/**
	 * @group install
	 */
	public function test_galerie_get_plugin_latest_stable_release_for_install() {
		$stable = PR_TESTING_ASSETS . '/releases-stable.atom';

		add_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = galerie_get_repository_json( 'test-plugin' );

		$release = galerie_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => 'latest',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );
		
		$this->assertTrue( $release->is_install );
	}
}
