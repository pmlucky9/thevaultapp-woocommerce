<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayPal Gateway.
 */
$settings = array(
	'basic' => array(
		'title'       => __( 'Basic Settings', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'title',
		'description' => '',
	),
	'enabled' => array(
		'title'   => __( 'Enable/Disable', 'woocommerce-gateway-thevaultapp' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable This Solution', 'woocommerce-gateway-thevaultapp' ),
		'description' => __( 'This enables PayPal Checkout which allows customers to checkout directly via PayPal from your cart page.', 'woocommerce-gateway-thevaultapp' ),
		'desc_tip'    => true,
		'default'     => 'yes',
	),	
	'environment' => array(
		'title'       => __( 'Environment', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'This setting specifies whether you will process live transactions, or whether you will process simulated transactions using the TheVaultApp Sandbox.', 'woocommerce-gateway-thevaultapp' ),
		'default'     => 'sandbox',
		'desc_tip'    => true,
		'options'     => array(
			'sandbox' => __( 'Sandbox', 'woocommerce-gateway-thevaultapp' ),
			'live'    => __( 'Live', 'woocommerce-gateway-thevaultapp' ),			
		),
	),
	'title' => array(
		'title'       => __( 'Title', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-thevaultapp' ),
		'default'     => __( 'Credit Card (TheVaultApp)', 'woocommerce-gateway-thevaultapp' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-thevaultapp' ),
		'default'     => __( 'Pay via VaultApp; you can pay with your phone using VaultApp.', 'woocommerce-gateway-thevaultapp' ),
	),

	'advanced' => array(
		'title'       => __( 'Advanced Settings', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'title',
		'description' => '',
	),
	'debug' => array(
		'title'       => __( 'Debug', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable Logging', 'woocommerce-gateway-thevaultapp' ),
		'default'     => 'no',
		'desc_tip'    => true,
		'description' => __( 'Log TheVaultApp events, such as API response.', 'woocommerce-gateway-thevaultapp' ),
	),

	'vault_enabled' => array(
		'title'       => __( 'Debug Log', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable Vault', 'woocommerce-gateway-thevaultapp' ),
		'default'     => 'no',
		'desc_tip'    => true,		
	),

	'accountkeys' => array(
		'title'       => __( 'Account Keys', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'title',
		'description' => '',
	),	
	
	'store_name' => array(
		'title'       => __( 'Store Name', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'text',
		'description' => __( 'A label that overrides the business name in the TheVaultApp account on the TheVaultApp hosted checkout pages.', 'woocommerce-gateway-thevaultapp' ),
		'default'     => get_bloginfo( 'name', 'display' ),
		'desc_tip'    => true,
	),
	'business_name' => array(
		'title'       => __( 'Business Name', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'text',
		'description' => __( '', 'woocommerce-gateway-thevaultapp' ),
		'default'     => get_bloginfo( 'name', 'display' ),
		'desc_tip'    => true,
	),

	'api_url' => array(
		'title'       => __( 'API Url', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'text',
		'description' => __( 'Get your API Url from VaultApp.', 'woocommerce-gateway-thevaultapp' ),
		'default'     => '',
		'desc_tip'    => true,		
	),
	'api_key' => array(
		'title'       => __( 'API Key', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'file',
		'description' => '',
		'default'     => '',
	),
	'callbackurls' => array(
		'title'       => __( 'Callback URLs', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'title',
		'description' => '',
	),
	'callback_url' => array(
		'title'       => __( 'Verify Callback', 'woocommerce-gateway-thevaultapp' ),
		'type'        => 'text',
		'description' => __( '', 'woocommerce-gateway-thevaultapp' ),
		'default'     => get_site_url() . 'thevaultapp/payment/vaultCallback',
		'desc_tip'    => true,
	),
);

return apply_filters( 'woocommerce_thevaultapp_settings', $settings );
