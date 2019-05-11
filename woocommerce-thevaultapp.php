<?php
/**
 * Plugin Name: TheVaultApp Checkout GateWay
 * Plugin URI: https://www.thevaultapp.com/
 * Description: A payment gateway for TheVaultApp (https://www.thevaultapp.com/).
 * Version: 1.0.0
 * Author: Pmlucky9
  * Copyright: © 2019 TheVaultApp.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'WC_GATEWAY_THEVAULTAPP_VERSION', '1.0.0' );

/**
 * Return instance of WC_Gateway_TheVaultApp_Plugin.
 *
 * @return WC_Gateway_TheVaultApp_Plugin
 */
function wc_gateway_thevaultapp() {
	static $plugin;

	if ( ! isset( $plugin ) ) {
		require_once( 'includes/class-wc-gateway-thevaultapp-plugin.php' );

		$plugin = new WC_Gateway_TheVaultApp_Plugin( __FILE__, WC_GATEWAY_THEVAULTAPP_VERSION );
	}

	return $plugin;
}

wc_gateway_thevaultapp()->maybe_run();


