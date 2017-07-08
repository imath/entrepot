<?php
/**
 * Functions tests.
 */

/**
 * @group functions
 */
class entrepot_Functions_Tests extends WP_UnitTestCase {

	public function repositories_dir() {
		return PR_TESTING_ASSETS;
	}

	/**
	 * @group update
	 */
	public function test_entrepot_get_plugin_latest_stable_release_for_update() {
		$stable = PR_TESTING_ASSETS . '/releases-stable.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.0.0-beta1',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( $release->is_update );
	}

	/**
	 * @group update
	 */
	public function test_entrepot_get_plugin_not_stable_release_for_update() {
		$stable = PR_TESTING_ASSETS . '/releases-not-stable.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.0.0-beta1',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertFalse( $release->is_update );
	}

	/**
	 * @group update
	 */
	public function test_entrepot_get_plugin_beta_latest_release_for_update() {
		$stable = PR_TESTING_ASSETS . '/releases-beta-after-stable.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.7.0',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertFalse( $release->is_update );
	}

	/**
	 * @group update
	 */
	public function test_entrepot_get_plugin_beta_latest_release_but_update() {
		$stable = PR_TESTING_ASSETS . '/releases-beta-after-stable.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.6.0',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( $release->is_update );
	}

	/**
	 * @group install
	 */
	public function test_entrepot_get_plugin_latest_stable_release_for_install() {
		$stable = PR_TESTING_ASSETS . '/releases-stable.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => 'latest',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( $release->is_install );
	}

	/**
	 * @group install
	 */
	public function test_entrepot_get_plugin_not_stable_release_for_install() {
		$stable = PR_TESTING_ASSETS . '/releases-not-stable.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => 'latest',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( ! isset( $release->is_install ) );
		$this->assertFalse( $release->is_update );
	}

	/**
	 * @group install
	 */
	public function test_entrepot_get_plugin_beta_after_release_for_install() {
		$stable = PR_TESTING_ASSETS . '/releases-beta-after-stable.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_plugin_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => 'latest',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		) );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( $release->is_install );
		$this->assertTrue( $release->version === '1.7.0' );
	}

	/**
	 * @group cache
	 */
	public function test_entrepot_get_repositories() {
		$repositories = entrepot_get_repositories();

		$entrepot = entrepot_get_repositories( 'entrepot' );
		$check   = wp_list_pluck( $repositories, 'releases' );
		$this->assertContains( $entrepot->releases, $check );

		$foo = entrepot_get_repositories( 'foo' );
		$this->assertEmpty( $foo );
	}

	/**
	 * @group dependencies
	 */
	public function test_entrepot_get_repository_dependencies() {
		$dependencies = array(
			(object) array( 'foo_bar_function' => 'Foo Bar Plugin' ),
			(object) array( 'taz_function'     => 'Taz Plugin' ),
			(object) array( 'entrepot_version' => 'Entrepôt' ),
		);

		$dependencies_data = entrepot_get_repository_dependencies( $dependencies );

		$this->assertSame( array( 'Foo Bar Plugin', 'Taz Plugin' ), $dependencies_data );
	}
}
