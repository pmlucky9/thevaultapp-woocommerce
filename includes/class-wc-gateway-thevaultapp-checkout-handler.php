<?php
/**
 * Cart handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$includes_path = wc_gateway_thevaultapp()->includes_path;

// TODO: Use spl autoload to require on-demand maybe?

require_once( $includes_path . 'class-wc-gateway-thevaultapp-settings.php' );

class WC_Gateway_TheVaultApp_Checkout_Handler {

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );		
	}

	/**
	 * If the buyer clicked on the "Check Out with TheVaultApp" button, we need to wait for the cart
	 * totals to be available.  Unfortunately that doesn't happen until
	 * woocommerce_before_cart_totals executes, and there is already output sent to the browser by
	 * this point.  So, to get around this issue, we'll enable output buffering to prevent WP from
	 * sending anything back to the browser.
	 */
	public function init() {
		if ( version_compare( WC_VERSION, '3.3', '<' ) ) {
			//add_filter( 'wc_checkout_params', array( $this, 'filter_wc_checkout_params' ), 10, 1 );
		} else {
			//add_filter( 'woocommerce_get_script_data', array( $this, 'filter_wc_checkout_params' ), 10, 2 );
		}
		if ( isset( $_GET['startcheckout'] ) && 'true' === $_GET['startcheckout'] ) {
			ob_start();
		}
	}
}
