<?php
/**
 * Cart handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$includes_path = WCGatewayThevaultapp()->includes_path;

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
	 * initialize function
	 */
	public function init() {		
		if ( isset( $_GET['startcheckout'] ) && 'true' === $_GET['startcheckout'] ) {
			ob_start();
		}
	}

	/**
	 * Frontend scripts
	 */
	public function enqueue_scripts() {	
		if (is_checkout()) {
			wp_enqueue_style( 'wc-gateway-thevaultapp-frontend-checkout', WCGatewayThevaultapp()->plugin_url . 'assets/css/wc-gateway-thevaultapp-frontend-checkout.css' );
			wp_enqueue_script( 'wc-gateway-thevaultapp-frontend-in-checkout', WCGatewayThevaultapp()->plugin_url . 'assets/js/wc-gateway-thevaultapp-checkout.js', array( 'jquery' ), WCGatewayThevaultapp()->version, true );
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
