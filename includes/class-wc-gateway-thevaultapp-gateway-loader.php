<?php
/**
 * Plugin bootstrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_TheVaultApp_Gateway_Loader {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$includes_path = WCGatewayThevaultapp()->includes_path;
		
		require_once( $includes_path . 'class-wc-gateway-thevaultapp-gateway.php' );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'payment_gateways' ) );
	}

	/**
	 * Register the TheVaultApp payment methods.
	 *
	 * @param array $methods Payment methods.
	 *
	 * @return array Payment methods
	 */
	public function payment_gateways( $methods ) {
		$methods[] = 'WC_Gateway_TheVaultApp'; 
		return $methods;
	}

	/**
	 * Checks whether gateway addons can be used.
	 *
	 * @return bool Returns true if gateway addons can be used
	 */
	public function can_use_addons() {
		return ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) );
	}
}
