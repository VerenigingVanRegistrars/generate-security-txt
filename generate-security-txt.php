<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.verenigingvanregistrars.nl/
 * @since             1.0.0
 * @package           Generate_Security_Txt
 *
 * @wordpress-plugin
 * Plugin Name:       Generate security.txt
 * Plugin URI:        https://wordpress.org/plugins/generate-security-txt/
 * Description:       Generate a PGP signed security.txt file with ease. Go to tools to generate the security.txt file or click below on 'Go to settings' to get started.
 * Version:           1.0.9
 * Author:            Vereniging van Registrars
 * Author URI:        https://www.verenigingvanregistrars.nl/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       generate-security-txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'GENERATE_SECURITY_TXT_VERSION', '1.0.9' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-generate-security-txt-activator.php
 */
function generate_security_txt_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-generate-security-txt-activator.php';
	Generate_Security_Txt_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-generate-security-txt-deactivator.php
 */
function generate_security_txt_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-generate-security-txt-deactivator.php';
	Generate_Security_Txt_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'generate_security_txt_activate' );
register_deactivation_hook( __FILE__, 'generate_security_txt_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-generate-security-txt.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-encryption.php';


/**
 * Return the plugin basefile
 *
 * @return string
 */
function generate_security_txt_get_basefile() {
    return plugin_basename(__FILE__);
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function generate_security_txt_run() {

	$plugin = new Generate_Security_Txt();
	$plugin->run();

}
generate_security_txt_run();
