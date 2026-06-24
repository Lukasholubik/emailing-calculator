<?php
/**
 * Plugin Name: Emailing Calculator
 * Description: Akviziční kalkulačka potenciálu emailingu pro e-shopy. Shortcode: [emailing_calculator]
 * Version: 1.5.4
 * Author: Lukáš Holubík
 * Text Domain: emailing-calculator
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ECALC_VERSION', '1.5.4' );
define( 'ECALC_PLUGIN_FILE', __FILE__ );
define( 'ECALC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ECALC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ── Auto-updater via GitHub ───────────────────────────────────────────────
$ecalc_updater_file = ECALC_PLUGIN_DIR . 'vendor/plugin-update-checker/load-v5p5.php';
if ( file_exists( $ecalc_updater_file ) ) {
	require_once $ecalc_updater_file;

	$ecalc_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/Lukasholubik/emailing-calculator/',
		ECALC_PLUGIN_FILE,
		'emailing-calculator'
	);

	// Používat GitHub Releases – umožňuje rollback na starší verzi
}

$ecalc_includes = [
	'helpers',
	'grou-admin-group',
	'class-ecalc-settings',
	'class-ecalc-activator',
	'class-ecalc-calculator',
	'class-ecalc-leads',
	'class-ecalc-email',
	'class-ecalc-smartemailing',
	'class-ecalc-cron',
	'class-ecalc-shortcode',
	'class-ecalc-rest',
	'class-ecalc-analytics',
	'class-ecalc-admin',
	'class-ecalc-plugin',
];

foreach ( $ecalc_includes as $file ) {
	require_once ECALC_PLUGIN_DIR . 'includes/' . $file . '.php';
}

register_activation_hook( __FILE__, [ 'ECAlc_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'ECAlc_Activator', 'deactivate' ] );

add_action( 'plugins_loaded', function () {
	if ( get_option( 'ecalc_db_version' ) !== ECALC_VERSION ) {
		ECAlc_Activator::create_leads_table();
	}
	( new ECAlc_Plugin() )->run();
} );
