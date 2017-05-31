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

	/**
	 * @group update
	 */
	public function test_galerie_get_plugin_latest_stable_release_for_update() {
		$stable = PR_TESTING_ASSETS . '/releases-stable.atom';

		add_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = galerie_get_repository_json( 'test-plugin' );

		$release = galerie_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.0.0-beta1',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( $release->is_update );
	}

	/**
	 * @group update
	 */
	public function test_galerie_get_plugin_not_stable_release_for_update() {
		$stable = PR_TESTING_ASSETS . '/releases-not-stable.atom';

		add_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = galerie_get_repository_json( 'test-plugin' );

		$release = galerie_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.0.0-beta1',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertFalse( $release->is_update );
	}

	/**
	 * @group update
	 */
	public function test_galerie_get_plugin_beta_latest_release_for_update() {
		$stable = PR_TESTING_ASSETS . '/releases-beta-after-stable.atom';

		add_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = galerie_get_repository_json( 'test-plugin' );

		$release = galerie_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.7.0',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertFalse( $release->is_update );
	}

	/**
	 * @group update
	 */
	public function test_galerie_get_plugin_beta_latest_release_but_update() {
		$stable = PR_TESTING_ASSETS . '/releases-beta-after-stable.atom';

		add_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = galerie_get_repository_json( 'test-plugin' );

		$release = galerie_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.6.0',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( $release->is_update );
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

	/**
	 * @group install
	 */
	public function test_galerie_get_plugin_not_stable_release_for_install() {
		$stable = PR_TESTING_ASSETS . '/releases-not-stable.atom';

		add_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = galerie_get_repository_json( 'test-plugin' );

		$release = galerie_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => 'latest',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'galerie_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( ! isset( $release->is_install ) );
		$this->assertFalse( $release->is_update );
	}

	/**
	 * @group install
	 */
	public function test_galerie_get_plugin_beta_after_release_for_install() {
		$stable = PR_TESTING_ASSETS . '/releases-beta-after-stable.atom';

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
		$this->assertTrue( $release->version === '1.7.0' );
	}
}
