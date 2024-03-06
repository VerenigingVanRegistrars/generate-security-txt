<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://geusmedia.nl/
 * @since      1.0.0
 *
 * @package    Generate_Security_Txt
 * @subpackage Generate_Security_Txt/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Generate_Security_Txt
 * @subpackage Generate_Security_Txt/includes
 * @author     Brian de Geus <brian@geusmedia.nl>
 */
class Generate_Security_Txt_i18n {

    // Define the text domain constant
    const TEXT_DOMAIN = 'generate-security-txt';

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			self::TEXT_DOMAIN,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
