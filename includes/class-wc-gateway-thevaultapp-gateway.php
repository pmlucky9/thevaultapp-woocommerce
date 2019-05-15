<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_TheVaultApp
 */
class WC_Gateway_TheVaultApp extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id = 'thevaultapp';
		$this->icon = 'https://www.thevaultapp.com/assets/static_pages_asset/img/logo.png';
		//plugin_dir_url( __FILE__ ) . 'images/facebook.png';//
		$this->has_fields         = false;
		$this->supports[]         = 'refunds';
		$this->method_title       = __( 'TheVaultApp Checkout', 'woocommerce-gateway-thevaultapp' );
		$this->method_description = __( 'Allow customers to conveniently checkout directly with TheValutApp.', 'woocommerce-gateway-thevaultapp' );
		

		$this->init_form_fields();
		$this->init_settings();

		$this->title        = $this->method_title;
		$this->description  = '';
		$this->enabled      = $this->get_option( 'enabled', 'yes' );
		$this->vault_enabled      = $this->get_option( 'enabled', 'yes' );		
		$this->environment  = $this->get_option( 'environment', 'stage' );
		$this->store_name    = $this->get_option( 'store_name' );
		$this->business_name    = $this->get_option( 'business_name' );
		$this->api_url   = $this->get_option( 'api_url' );
		$this->api_key = $this->get_option( 'api_key' );

		$this->debug                      = 'yes' === $this->get_option( 'debug', 'yes' );
		$this->callback_url = $this->get_option( 'callback_url' );					

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Change gateway name if session is active
		if ( ! is_admin() ) {
			//if ( wc_gateway_thevaultapp()->checkout->is_started_from_checkout_page() ) {
				$this->title        = $this->get_option( 'title' );
				$this->description  = $this->get_option( 'description' );
			//}			
		} else {
			//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}		

		// Add callback functions		
		add_action( 'woocommerce_api_wc_gateway_thevaultapp', array( $this, 'callback_handler' ) );

		// Make billing phone number required
		add_filter( 'woocommerce_billing_fields', array( $this, 'wps_add_filter_phone'), 10, 1 );
	}

	/**
	 * Pass woo return args to AJAX endpoint when the checkout updates from the frontend
	 * so that the order button gets set correctly.
	 *
	 * @param  string $request Optional.
	 * @return string
	 */
	public function pass_return_args_to_ajax( $request ) {
		if ( isset( $_GET['woo-thevaultapp-return'] ) ) {
			$request .= '&woo-thevaultapp-return=1';
		}

		return $request;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include( dirname( dirname( __FILE__ ) ) . '/includes/settings/settings-thevaultapp.php' );		
	}

	/**
	 * Process payments.
	 *
	 * @param int $order_id Order ID
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {		
		global $woocommerce;
		
		// Get an instance of the WC_Order object
		$order = wc_get_order( $order_id );

		$order_result = send_vault_order($order, $this->api_url, $this->api_key, $this->store_name);		
		
		if ($order_result['status'] != 'ok')
		{
			wc_add_notice( __('Payment error:', 'woothemes') . $order_result['errors'][0], 'error' );
			return;
		}
		
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' => 'success',
			'data' => $order_result,
			'redirect' => $this->get_return_url( $order )
		);
	}

	/**
	 * Process refund.
	 *
	 * @param int    $order_id Order ID
	 * @param float  $amount   Order amount
	 * @param string $reason   Refund reason
	 *
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
	}

	/**
	 * Get the transaction URL.
	 *
	 * @param  WC_Order $order
	 * @return string
	 */
	public function get_transaction_url( $order ) {
		if ( 'sandbox' === $this->environment ) {
			$this->view_transaction_url = '';
		} else {
			$this->view_transaction_url = '';
		}
		return parent::get_transaction_url( $order );
	}

	/**
	 * Check if this gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_available() {
		return 'yes' === $this->enabled;
	}

	/**
	 * Register callback functions
	 */
	public function register_routes() {
		register_rest_route('thevaultapp/v1', '/callback' ,
		  array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array($this, 'callback_handler'),
		  )); 
	  }

	/**
	 * Callback functions
	 * 
	 * $param $request Request array
	 */
	 public function callback_handler() {	
		global $woocommerce;
		try {
			// get post data
			$obj = json_decode(file_get_contents('php://input'), true);
			if (!isset($obj['subid1']))
				wp_die( $e->getMessage(), esc_html__( 'TheVaultApp Request Failure', 'woocommerce-gateway-thevaultapp' ), array( 'response' => 500 ) );

			// get order object
			$order_id = $obj['subid1'];			
			$order = wc_get_order( $order_id );

			if ($order == false) {
				wc_add_notice( __('Payment error:', 'woothemes') . 'Order not found', 'error' );
				return;
			}
			
			$status = strtolower(trim($obj['status']));

			if ($status === 'approved') {
				// Payment complete
				$order->payment_complete();

				$order->update_status('completed', __( 'Completed payment', 'woocommerce' ));

				// Return thank you page redirect
				return array(
					'result' => 'approved',
					'redirect' => $this->get_return_url( $order )
				);
				
			} else {
				$order->update_status('cancelled', __( 'Cancelled payment', 'woocommerce' ));

				// Return order page redirect
				return array(
					'result' => 'cancelled',
					'redirect' => $this->get_return_url( $order )
				);			
			}


		} catch ( Exception $e ) {
			wp_die( $e->getMessage(), esc_html__( 'TheVaultApp Request Failure', 'woocommerce-gateway-thevaultapp' ), array( 'response' => 500 ) );
		}
	}

	/**
	 *  Make billing phone number to required field
	 * 
	 * @param $address_fields
	 * 
	 * @return Object
	 */	
	public function wps_add_filter_phone( $address_fields ) {
		$address_fields['billing_phone']['required'] = true;
		return $address_fields;
	}

}
