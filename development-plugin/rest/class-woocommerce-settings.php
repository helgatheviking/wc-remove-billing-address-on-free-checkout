<?php
/**
 * Add additional settings (`woocommerce_ship_to_destination`) to  `/wp-json/wp/v2/settings`.
 *
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Rest;

/**
 * A bit hacky – modify `global $wp_registered_settings` before it is used.
 */
class WooCommerce_Settings {

	/**
	 * Add hooks to register the REST endpoints.
	 */
	public function register_hooks(): void {
		add_filter( 'rest_pre_dispatch', $this->show_settings_in_rest( ... ) );
	}

	/**
	 * Expose settings through the REST API.
	 *
	 * `woocommerce_checkout_page_id`
	 *
	 * @hooked rest_pre_dispatch
	 * @see register_setting()
	 *
	 * @param null|mixed $short_circuit The value to return.
	 *
	 * @see get_registered_settings
	 * /wp-json/wp/v2/settings
	 *
	 * @see WP_REST_Settings_Controller
	 */
	public function show_settings_in_rest( mixed $short_circuit ): mixed {
		global $wp_registered_settings;

		$new_settings = array(
			'woocommerce_ship_to_destination' => array(
				'show_in_rest' => true,
				'type'         => 'string',
			),
		);

		foreach ( $new_settings as $setting_name => $setting_coniguration ) {
			if ( ! in_array( $setting_name, $wp_registered_settings, true ) ) {
				$wp_registered_settings[ $setting_name ] = $setting_coniguration;
			} else {
				$wp_registered_settings[ $setting_name ]['show_in_rest'] = true;
			}
		}
		return $short_circuit;
	}
}
