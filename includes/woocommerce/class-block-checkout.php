<?php
/**
 *
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use WC_Payment_Gateways;

/**
 * @see \Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock::register_block_type_assets()
 */
class Block_Checkout implements IntegrationInterface {

	/**
	 * Return a unique name for the integration.
	 *
	 * @see IntegrationInterface::get_name()
	 * @return string
	 */
	public function get_name() {
		return 'wc-remove-billing-address-on-free-checkout';
	}

	/**
	 * Initialize the integration – register the script and add filters.
	 *
	 * @see IntegrationInterface::initialize()
	 * @return void
	 */
	public function initialize() {
		$this->register_script();
		add_filter( 'woocommerce_get_country_locale', array( $this, 'hide_address_fields_for_free_virtual_cart' ) );
	}

	/**
	 * Hide billing address fields at page-load time when the cart is free and
	 * requires no shipping (i.e. all items are virtual/downloadable).
	 *
	 * @param array $locale Country locale data from WooCommerce.
	 * @return array Modified locale with address fields hidden.
	 */
	public function hide_address_fields_for_free_virtual_cart( array $locale ): array {
		$cart = WC()->cart;
		if ( ! $cart || $cart->needs_shipping() ) {
			return $locale;
		}
		if ( 0.0 !== floatval( $cart->get_total( 'edit' ) ) ) {
			return $locale;
		}
		$fields_to_hide = [ 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone' ];
		foreach ( array_keys( $locale ) as $country_code ) {
			foreach ( $fields_to_hide as $field ) {
				$locale[ $country_code ][ $field ] = [
					'required' => false,
					'hidden'   => true,
				];
			}
		}
		return $locale;
	}

	/**
	 * Register the checkout script with WordPress, to later be enqueued by WooCommerce.
	 */
	protected function register_script(): void {
		$script_asset_path = realpath( __DIR__ . '/../../build/wc-remove-billing-address-on-free-checkout-blocks.asset.php' );
		$script_asset      = $script_asset_path && file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => '2.0.0',
			);

		wp_register_script(
			'wc-remove-billing-address-on-free-checkout-blocks',
			plugins_url( 'build/wc-remove-billing-address-on-free-checkout-blocks.js', __DIR__ . '/../../' ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * Return the handle of the script registered above.
	 *
	 * @see IntegrationInterface::get_script_handles()
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wc-remove-billing-address-on-free-checkout-blocks' );
	}

	/**
	 * The script does not run in the admin editor.
	 *
	 * @see IntegrationInterface::get_editor_script_handles()
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * Return the list of gateway IDs that don't require billing fields.
	 * This data is passed to the JS integration via wp_localize_script.
	 *
	 * @see IntegrationInterface::get_script_data()
	 * @return array<string,mixed>
	 */
	public function get_script_data(): array {
		$gateways   = WC_Payment_Gateways::instance()->payment_gateways();
		$no_billing = [];
		foreach ( $gateways as $id => $gateway ) {
			if ( property_exists( $gateway, 'require_billing_fields' ) && false === $gateway->require_billing_fields ) {
				$no_billing[] = $id;
			}
			if ( 'cod' === $id ) {
				$no_billing[] = $id;
			}
		}
		return [ 'no_billing_gateways' => array_unique( $no_billing ) ];
	}
}
