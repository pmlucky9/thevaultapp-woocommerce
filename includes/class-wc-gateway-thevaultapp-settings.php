<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings retrieval from the settings API.
 */
class WC_Gateway_TheVaultApp_Settings {

	/**
	 * Setting values from get_option.
	 *
	 * @var array
	 */
	protected $_settings = array();

	/**
	 * Flag to indicate setting has been loaded from DB.
	 *
	 * @var bool
	 */
	private $_is_setting_loaded = false;

	public function __set( $key, $value ) {
		if ( array_key_exists( $key, $this->_settings ) ) {
			$this->_settings[ $key ] = $value;
		}
	}

	public function __get( $key ) {
		if ( array_key_exists( $key, $this->_settings ) ) {
			return $this->_settings[ $key ];
		}
		return null;
	}

	public function __isset( $key ) {
		return array_key_exists( $key, $this->_settings );
	}

	public function __construct() {
		$this->load();
	}

	/**
	 * Load settings from DB.
	 *
	 * @param bool $force_reload Force reload settings
	 *
	 * @return WC_Gateway_TheVaultApp_Settings Instance of WC_Gateway_TheVaultApp_Settings
	 */
	public function load( $force_reload = false ) {
		if ( $this->_is_setting_loaded && ! $force_reload ) {
			return $this;
		}
		$this->_settings          = (array) get_option( 'woocommerce_thevaultapp_settings', array() );
		$this->_is_setting_loaded = true;
		return $this;
	}

	/**
	 * Load settings from DB.
	 *
	 * @deprecated
	 */
	public function load_settings( $force_reload = false ) {
		_deprecated_function( __METHOD__, '1.2.0', 'WC_Gateway_TheVaultApp_Settings::load' );
		return $this->load( $force_reload );
	}

	/**
	 * Save current settings.
	 *
	 * @since 1.2.0
	 */
	public function save() {
		update_option( 'woocommerce_thevaultapp_settings', $this->_settings );
	}


	/**
	 * Is TheValutApp enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return 'yes' === $this->enabled;
	}

	/**
	 * Is logging enabled.
	 *
	 * @return bool
	 */
	public function is_logging_enabled() {
		return 'yes' === $this->debug;
	}

	/**
	 * Get session length.
	 *
	 * @todo Map this to a merchant-configurable setting
	 *
	 * @return int
	 */
	public function get_token_session_length() {
		return 10800; // 3h
	}

}
