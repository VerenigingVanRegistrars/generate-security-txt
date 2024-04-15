<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://verenigingvanregistrars.nl/
 * @since      1.0.0
 *
 * @package    Generate_Security_Txt
 * @subpackage Generate_Security_Txt/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Generate_Security_Txt
 * @subpackage Generate_Security_Txt/includes
 * @author     Brian de Geus <wordpress@verenigingvanregistrars.nl>
 */
class Generate_Security_Txt_Deactivator {

	/**
	 * Some functions to clean up, mainly unregistering cron
     * TODO; Maybe add removing of keys and files. Not sure if users would want this.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        wp_clear_scheduled_hook('check_securitytxt_expiration_event');
	}
}
