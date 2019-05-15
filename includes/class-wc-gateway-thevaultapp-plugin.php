<?php
/**
 * TheValueApp Checkout Plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_TheVaultApp_Plugin {

	const ALREADY_BOOTSTRAPED = 1;
	const DEPENDENCIES_UNSATISFIED = 2;
	const NOT_CONNECTED = 3;

	/**
	 * Filepath of main plugin file.
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Absolute plugin path.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Absolute plugin URL.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Absolute path to plugin includes dir.
	 *
	 * @var string
	 */
	public $includes_path;

	/**
	 * Flag to indicate the plugin has been boostrapped.
	 *
	 * @var bool
	 */
	private $_bootstrapped = false;

	/**
	 * Instance of WC_Gateway_TheVaultApp_Settings.
	 *
	 * @var WC_Gateway_TheVaultApp_Settings
	 */
	public $settings;

	/**
	 * Constructor.
	 *
	 * @param string $file    Filepath of main plugin file
	 * @param string $version Plugin version
	 */
	public function __construct( $file, $version ) {
		$this->file    = $file;
		$this->version = $version;

		// Path.
		$this->plugin_path   = trailingslashit( plugin_dir_path( $this->file ) );
		$this->plugin_url    = trailingslashit( plugin_dir_url( $this->file ) );
		$this->includes_path = $this->plugin_path . trailingslashit( 'includes' );
	}

	
	/**
	 * Start the plugin.
	 */
	public function start() {
		register_activation_hook( $this->file, array( $this, 'activate' ) );

		add_action( 'plugins_loaded', array( $this, 'bootstrap' ) );
		add_filter( 'allowed_redirect_hosts' , array( $this, 'whitelist_thevaultapp_domains_for_redirect' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this, 'plugin_action_links' ) );	

	}

	public function bootstrap() {
		try {
			if ( $this->_bootstrapped ) {
				throw new Exception( __( '%s in WooCommerce Gateway TheVaultApp plugin can only be called once', 'woocommerce-gateway-thevaultapp' ), self::ALREADY_BOOTSTRAPED );
			}

			$this->_check_dependencies();
			$this->_run();				
			$this->_bootstrapped = true;
		} catch ( Exception $e ) {
			if ( in_array( $e->getCode(), array( self::ALREADY_BOOTSTRAPED, self::DEPENDENCIES_UNSATISFIED ) ) ) {
				$this->bootstrap_warning_message = $e->getMessage();
			}

			if ( self::NOT_CONNECTED === $e->getCode() ) {
				$this->prompt_to_connect = $e->getMessage();
			}
		}
	}

	/**
	 * Check dependencies.
	 *
	 * @throws Exception
	 */
	protected function _check_dependencies() {
		if ( ! function_exists( 'WC' ) ) {
			throw new Exception( __( 'WooCommerce Gateway TheVaultApp Checkout requires WooCommerce to be activated', 'woocommerce-gateway-thevaultapp' ), self::DEPENDENCIES_UNSATISFIED );
		}

		if ( version_compare( WC()->version, '2.5', '<' ) ) {
			throw new Exception( __( 'WooCommerce Gateway TheVaultApp Checkout requires WooCommerce version 2.5 or greater', 'woocommerce-gateway-thevaultapp' ), self::DEPENDENCIES_UNSATISFIED );
		}

		if ( ! function_exists( 'curl_init' ) ) {
			throw new Exception( __( 'WooCommerce Gateway TheVaultApp Checkout requires cURL to be installed on your server', 'woocommerce-gateway-thevaultapp' ), self::DEPENDENCIES_UNSATISFIED );
		}

		$openssl_warning = __( 'WooCommerce Gateway TheVaultApp Checkout requires OpenSSL >= 1.0.1 to be installed on your server', 'woocommerce-gateway-thevaultapp' );
		if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
			throw new Exception( $openssl_warning, self::DEPENDENCIES_UNSATISFIED );
		}

		preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
		if ( empty( $matches[1] ) ) {
			throw new Exception( $openssl_warning, self::DEPENDENCIES_UNSATISFIED );
		}

		if ( ! version_compare( $matches[1], '1.0.1', '>=' ) ) {
			throw new Exception( $openssl_warning, self::DEPENDENCIES_UNSATISFIED );
		}
	}

	/**
	 * Run the plugin.
	 */
	protected function _run() {
		require_once( $this->includes_path . 'functions.php' );
		$this->_load_handlers();		
	}

	/**
	 * Callback for activation hook.
	 */
	public function activate() {
		if ( ! isset( $this->settings ) ) {
			require_once( $this->includes_path . 'class-wc-gateway-thevaultapp-settings.php' );
			$settings = new WC_Gateway_TheVaultApp_Settings();
		} else {
			$settings = $this->settings;
		}
	}

	/**
	 * Load handlers.
	 */
	protected function _load_handlers() {
		// Load handlers.
		require_once( $this->includes_path . 'class-wc-gateway-thevaultapp-settings.php' );
		require_once( $this->includes_path . 'class-wc-gateway-thevaultapp-gateway-loader.php' );		
		require_once( $this->includes_path . 'class-wc-gateway-thevaultapp-admin-handler.php' );
		require_once( $this->includes_path . 'class-wc-gateway-thevaultapp-checkout-handler.php' );		

		$this->settings       = new WC_Gateway_TheVaultApp_Settings();
		$this->gateway_loader = new WC_Gateway_TheVaultApp_Gateway_Loader();	
		$this->admin          = new WC_Gateway_TheVaultApp_Admin_Handler();
		$this->checkout       = new WC_Gateway_TheVaultApp_Checkout_Handler();				
	}

	/**
	 * Link to settings screen.
	 */
	public function get_admin_setting_link() {
		if ( version_compare( WC()->version, '2.6', '>=' ) ) {
			$section_slug = 'thevaultapp';
		} else {
			$section_slug = strtolower( 'WC_Gateway_TheVaultApp' );
		}
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}

	/**
	 * Allow TheVaultApp domains for redirect.
	 *
	 * @param array $domains Whitelisted domains for `wp_safe_redirect`
	 *
	 * @return array $domains Whitelisted domains for `wp_safe_redirect`
	 */
	public function whitelist_thevaultapp_domains_for_redirect( $domains ) {
		$domains[] = 'staging.thevaultapp.com';
		$domains[] = 'www.thevaultapp.com';		
		return $domains;
	}

	/**
	 * Load localisation files.
	 *
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-gateway-thevaultapp', false, plugin_basename( $this->plugin_path ) . '/languages' );
	}

		/**
	 * Add relevant links to plugins page.
	 *
	 * @param array $links Plugin action links
	 *
	 * @return array Plugin action links
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array();

		if ( function_exists( 'WC' ) ) {
			$setting_url = $this->get_admin_setting_link();
			$plugin_links[] = '<a href="' . esc_url( $setting_url ) . '">' . esc_html__( 'Settings', 'woocommerce-gateway-thevaultapp' ) . '</a>';
		}

		$plugin_links[] = '<a href="http://document.thevaultapp.com/">' . esc_html__( 'Docs', 'woocommerce-gateway-thevaultapp' ) . '</a>';		

		return array_merge( $plugin_links, $links );
	}
}
