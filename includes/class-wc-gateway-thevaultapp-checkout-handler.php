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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );	

		add_action( 'wp_ajax_nopriv_fetch_order_status',  array( $this, 'fetch_order_status' ));
		add_action( 'wp_ajax_fetch_order_status', array( $this, 'fetch_order_status' ));
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

	/**
	 * Frontend scripts
	 */
	public function enqueue_scripts() {	
		if (is_checkout()) {
			wp_enqueue_style( 'wc-gateway-thevaultapp-frontend-checkout', wc_gateway_thevaultapp()->plugin_url . 'assets/css/wc-gateway-thevaultapp-frontend-checkout.css' );
			wp_enqueue_script( 'wc-gateway-thevaultapp-frontend-in-checkout', wc_gateway_thevaultapp()->plugin_url . 'assets/js/wc-gateway-thevaultapp-checkout.js', array( 'jquery' ), wc_gateway_thevaultapp()->version, true );
		}
	}

	/**
	 * Ajax Fetch order status
	 */
	public function fetch_order_status(){	
		$order = wc_get_order( $_REQUEST['order_id'] );		
		$order_data = $order->get_data();
		echo $order_data['status'];
		die();
	}
}
