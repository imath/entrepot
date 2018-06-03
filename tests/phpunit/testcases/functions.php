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

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.0.0-beta1',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( $release->is_update );
	}

	/**
	 * @group update
	 */
	public function test_entrepot_get_plugin_latest_stable_release_two_digits_in_atom_for_update() {
		$stable = PR_TESTING_ASSETS . '/releases-two-digits-stable.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.0.0',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertTrue( $release->is_update );
	}

	/**
	 * @group update
	 */
	public function test_entrepot_get_plugin_latest_stable_release_two_digits_in_version_for_update() {
		$stable = PR_TESTING_ASSETS . '/releases-stable.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.0',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$this->assertFalse( $release->is_update );
	}

	/**
	 * @group update
	 */
	public function test_entrepot_get_plugin_latest_stable_release_no_dots_in_version_for_update() {
		$stable = PR_TESTING_ASSETS . '/releases.atom';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$json = entrepot_get_repository_json( 'test-plugin' );

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '39',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

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

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.0.0-beta1',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

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

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.7.0',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

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

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => '1.6.0',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

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

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => 'latest',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

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

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => 'latest',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

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

		$release = entrepot_get_repository_latest_stable_release( $stable, array(
			'plugin'            => $json->name,
			'slug'              => 'test-plugin',
			'Version'           => 'latest',
			'GitHub Plugin URI' => rtrim( $json->releases, '/releases' ),
		), 'plugin' );

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

	/**
	 * @group upgrades
	 */
	public function test_entrepot_get_upgrader_tasks_filter() {
		global $test_upgrade_db_version;
		$reset_global = $test_upgrade_db_version;

		$test_upgrade_db_version = '1.0.0';

		require_once PR_TESTING_ASSETS . '/test-upgrade.php';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );
		add_filter( 'entrepot_add_upgrader_tasks', 'test_upgrade_add_upgrade_routines' );

		$upgrade = entrepot_get_upgrader_tasks();

		$this->assertTrue( isset( $upgrade['test-upgrade'] ) );
		$this->assertTrue( isset( $upgrade['test-upgrade']['info']['icon'] ) );
		$this->assertTrue( 1 === count( $upgrade['test-upgrade']['tasks'] ) );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );
		remove_filter( 'entrepot_add_upgrader_tasks', 'test_upgrade_add_upgrade_routines' );
		$test_upgrade_db_version = $reset_global;
	}

	/**
	 * @group upgrades
	 */
	public function test_entrepot_get_upgrader_tasks_filter_none() {
		global $test_upgrade_db_version;
		$reset_global = $test_upgrade_db_version;

		$test_upgrade_db_version = '2.0.0';

		require_once PR_TESTING_ASSETS . '/test-upgrade.php';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );
		add_filter( 'entrepot_add_upgrader_tasks', 'test_upgrade_add_upgrade_routines' );

		$upgrade = entrepot_get_upgrader_tasks();

		$this->assertFalse( isset( $upgrade['test-upgrade'] ) );

		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );
		remove_filter( 'entrepot_add_upgrader_tasks', 'test_upgrade_add_upgrade_routines' );
		$test_upgrade_db_version = $reset_global;
	}

	/**
	 * @group upgrades
	 */
	public function test_entrepot_register_upgrade_tasks() {
		$reset = entrepot()->upgrades;

		entrepot_register_upgrade_tasks( 'foo-bar', '1.0.0', array(
			'1.1.0' => array( array(
				'callback' => '__return_true',
				'count'    => '__return_true',
				'message'  => 'foobar',
				'number'   => 1,
			), ),
		) );

		entrepot_register_upgrade_tasks( 'bar-foo', '1.0.0', array(
			'1.1.0' => array( array(
				'callback' => '__return_true',
				'count'    => '__return_true',
				'message'  => 'barfoo',
				'number'   => 1,
			), ),
		) );

		$this->assertSame( array( 'foo-bar', 'bar-foo' ), array_keys( wp_list_pluck( entrepot()->upgrades, 'slug' ) ) );

		entrepot()->upgrades = $reset;
	}

	/**
	 * @group upgrades
	 */
	public function test_entrepot_register_upgrade_tasks_unique_slug() {
		$reset = entrepot()->upgrades;

		entrepot_register_upgrade_tasks( 'foo-bar', '1.0.0', array(
			'1.1.0' => array( array(
				'callback' => '__return_true',
				'count'    => '__return_true',
				'message'  => 'foobar',
				'number'   => 1,
			), ),
		) );

		entrepot_register_upgrade_tasks( 'foo-bar', '1.0.0', array(
			'1.1.0' => array( array(
				'callback' => '__return_true',
				'count'    => '__return_true',
				'message'  => 'barfoo',
				'number'   => 1,
			), ),
		) );

		$this->assertTrue( 'foobar' === entrepot()->upgrades['foo-bar']->tasks['1.1.0'][0]['message'] );

		entrepot()->upgrades = $reset;
	}

	/**
	 * @group upgrades
	 */
	public function test_entrepot_unregister_upgrade_tasks() {
		$reset = entrepot()->upgrades;

		entrepot_register_upgrade_tasks( 'foo-bar', '1.0.0', array(
			'1.1.0' => array( array(
				'callback' => '__return_true',
				'count'    => '__return_true',
				'message'  => 'foobar',
				'number'   => 1,
			), ),
		) );

		entrepot_unregister_upgrade_tasks( 'foo-bar' );

		$this->assertEmpty( entrepot()->upgrades );
	}

	/**
	 * @group upgrades
	 */
	public function test_entrepot_get_upgrader_tasks_action() {
		global $test_upgrade_db_version;
		$reset_global = $test_upgrade_db_version;
		$reset = entrepot()->upgrades;

		$test_upgrade_db_version = '1.0.0';

		require_once PR_TESTING_ASSETS . '/test-upgrade.php';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );
		add_filter( 'entrepot_add_upgrader_tasks', 'test_upgrade_add_upgrade_routines' );

		$upgrade_filter = entrepot_get_upgrader_tasks();

		remove_filter( 'entrepot_add_upgrader_tasks', 'test_upgrade_add_upgrade_routines' );
		add_action( 'entrepot_register_upgrade_tasks', 'test_upgrade_register_upgrade_routines' );

		$upgrade_action = entrepot_get_upgrader_tasks();

		$this->assertEquals( $upgrade_action, $upgrade_filter );

		remove_action( 'entrepot_register_upgrade_tasks', 'test_upgrade_register_upgrade_routines' );
		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$test_upgrade_db_version = $reset_global;
		entrepot()->upgrades = $reset;
	}

	/**
	 * @group upgrades
	 */
	public function test_entrepot_get_upgrader_tasks_multiple_versions() {
		global $test_upgrade_db_version;
		$reset_global = $test_upgrade_db_version;
		$reset = entrepot()->upgrades;

		$test_upgrade_db_version = '1.9.0';

		require_once PR_TESTING_ASSETS . '/test-upgrade.php';

		add_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );
		add_action( 'entrepot_register_upgrade_tasks', 'test_upgrade_register_upgrade_multiple_versions' );

		$upgrade_action = entrepot_get_upgrader_tasks();

		$this->assertEquals( array( 'Upgrading to 2.0.0', 'Upgrading to 2.1.0' ), wp_list_pluck( $upgrade_action['test-upgrade']['tasks'], 'message' ) );

		remove_action( 'entrepot_register_upgrade_tasks', 'test_upgrade_register_upgrade_multiple_versions' );
		remove_filter( 'entrepot_plugins_dir', array( $this, 'repositories_dir' ) );

		$test_upgrade_db_version = $reset_global;
		entrepot()->upgrades = $reset;
	}
}
