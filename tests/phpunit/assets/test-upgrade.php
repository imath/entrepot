<?php
/**
 * Plugin Name: Test Upgrade
 * Plugin URI: https://github.com/imath/test-upgrade/
 * Description: A plugin to test upgrade.
 * Version: 2.0.0
 * Requires at least: 4.8
 * Tested up to: 4.8
 * License: GNU/GPL 2
 * Author: imath
 * Author URI: https://imathi.eu/
 * Text Domain: test-upgrade
 * Domain Path: /languages/
 * GitHub Plugin URI: https://github.com/imath/test-upgrade/
 */

function test_upgrade_get_db_version() {
	global $test_upgrade_db_version;

	return $test_upgrade_db_version;
}

function test_upgrade_get_version() {
	return '2.0.0';
}

function test_upgrade_dummy_task() {
	return 1;
}

function test_upgrade_get_tasks() {
	return array(
		'2.0.0' => array(
			array(
				'callback' => 'test_upgrade_dummy_task',
				'count'    => 'test_upgrade_dummy_task',
				'message'  => 'foo message',
				'number'   => 1,
			),
		)
	);
}

function test_upgrade_get_multiple_versions() {
	return array(
		'1.9.0' => array(
			array(
				'callback' => 'test_upgrade_dummy_task',
				'count'    => 'test_upgrade_dummy_task',
				'message'  => 'Upgrading to 1.9.0',
				'number'   => 1,
			),
		),
		'2.0.0' => array(
			array(
				'callback' => 'test_upgrade_dummy_task',
				'count'    => 'test_upgrade_dummy_task',
				'message'  => 'Upgrading to 2.0.0',
				'number'   => 1,
			),
		),
		'2.1.0' => array(
			array(
				'callback' => 'test_upgrade_dummy_task',
				'count'    => 'test_upgrade_dummy_task',
				'message'  => 'Upgrading to 2.1.0',
				'number'   => 1,
			),
		),
	);
}

function test_upgrade_add_upgrade_routines( $tasks = array() ) {
	$db_version = test_upgrade_get_db_version();

	// We are not using the Entrepôt for install.
	if ( 0 === (int) $db_version ) {
		return $tasks;
	}

	if ( version_compare( $db_version, test_upgrade_get_version(), '<' ) ) {
		$tasks['test-upgrade'] = (object) array(
			'slug'           => 'test-upgrade',
			'db_version'     => $db_version,
			'tasks'          => test_upgrade_get_tasks(),
		);
	}

	return $tasks;
}

function test_upgrade_register_upgrade_routines() {
	entrepot_register_upgrade_tasks( 'test-upgrade', test_upgrade_get_db_version(), test_upgrade_get_tasks() );
}

function test_upgrade_register_upgrade_multiple_versions() {
	entrepot_register_upgrade_tasks( 'test-upgrade', test_upgrade_get_db_version(), test_upgrade_get_multiple_versions() );
}
