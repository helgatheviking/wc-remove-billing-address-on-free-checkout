<?php
/**
 *
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use WC_Order;
use WC_Payment_Gateways;

/**
 * @see \Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock::register_block_type_assets()
 */
class Block_Checkout implements IntegrationInterface {

	/**
	 * Let WooCommerce know about the plugin in its `checkout_block` registry.
	 *
	 * @hooked woocommerce_blocks_checkout_block_registration
	 * @see IntegrationRegistry::initialize()
	 *
	 * @param IntegrationRegistry $integration_registry The WooCommerce instance for blocks checkout integrations.
	 */
	public function register_integration( IntegrationRegistry $integration_registry ): void {
		$integration_registry->register( $this );
	}

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
	 * Initialize the integration – register the frontend script.
	 *
	 * Note: locale filters are registered in the main plugin class
	 * (WC_Remove_Billing_Address_On_Free_Checkout::define_block_checkout_hooks)
	 * so they also fire during Store API requests, where BlockTypesController
	 * is skipped and this initialize() method is never called.
	 *
	 * @see IntegrationInterface::initialize()
	 * @return void
	 */
	public function initialize() {
		$this->register_script();
	}

	/**
	 * Hide billing address fields at page-load time for free + no-shipping carts,
	 * or when a gateway that doesn't require billing fields is selected.
	 *
	 * Filters the per-country locale array used by WooCommerce to determine which
	 * checkout fields are required/hidden. Because WC_Countries::get_country_locale()
	 * caches this data, both `woocommerce_get_country_locale` (for explicit per-country
	 * entries) and `woocommerce_get_country_locale_default` (for the default/fallback)
	 * must be filtered.
	 *
	 * During a Store API checkout request, WooCommerce calls WC()->session->set(
	 * 'chosen_payment_method', ...) in update_order_from_request() BEFORE
	 * validate_address_fields() calls get_country_locale() — so the session already
	 * holds the chosen gateway when this filter runs.
	 *
	 * @param array $locale Country-keyed locale array (country_code => field definitions).
	 * @return array Modified locale with address fields hidden.
	 */
	public function hide_address_fields_for_free_virtual_cart( array $locale ): array {
		if ( ! $this->is_checkout_context() ) {
			return $locale;
		}

		$cart = WC()->cart;
		if ( ! $cart ) {
			return $locale;
		}

		$is_free_virtual = ! $cart->needs_shipping() && 0.0 === floatval( $cart->get_total( 'edit' ) );

		if ( ! $is_free_virtual && ! $this->is_no_billing_gateway_selected() ) {
			return $locale;
		}

		$fields_to_hide = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone' );
		foreach ( array_keys( $locale ) as $country_code ) {
			foreach ( $fields_to_hide as $field ) {
				$locale[ $country_code ][ $field ] = array(
					'required' => false,
					'hidden'   => true,
				);
			}
		}
		return $locale;
	}

	/**
	 * Hide billing address fields in the default locale for free + no-shipping carts,
	 * or when a gateway that doesn't require billing fields is selected.
	 *
	 * The `woocommerce_get_country_locale_default` filter receives a flat field-keyed
	 * array rather than the country-keyed array of `woocommerce_get_country_locale`.
	 * Setting `hidden: true` here ensures that even for countries whose locale entry
	 * doesn't appear in the explicit per-country array, the default-based merge in
	 * OrderController::validate_address_fields() still skips validation for those fields.
	 *
	 * @param array $locale Flat field-keyed locale array (field_name => field properties).
	 * @return array Modified locale with address fields marked not-required and hidden.
	 */
	public function hide_address_fields_default_locale_for_free_virtual_cart( array $locale ): array {
		if ( ! $this->is_checkout_context() ) {
			return $locale;
		}

		$cart = WC()->cart;
		if ( ! $cart ) {
			return $locale;
		}

		$is_free_virtual = ! $cart->needs_shipping() && 0.0 === floatval( $cart->get_total( 'edit' ) );

		if ( ! $is_free_virtual && ! $this->is_no_billing_gateway_selected() ) {
			return $locale;
		}

		$fields_to_hide = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone' );
		foreach ( $fields_to_hide as $field ) {
			$locale[ $field ] = array_merge(
				$locale[ $field ] ?? array(),
				array(
					'required' => false,
					'hidden'   => true,
				)
			);
		}
		return $locale;
	}

	/**
	 * Whether the current request is a checkout page render or a Store API request.
	 *
	 * The locale filters must only apply in these contexts. On other pages (e.g.
	 * /my-account/edit-address/billing/) the WC session may still hold the last
	 * chosen payment method, which would incorrectly hide billing fields.
	 *
	 * @return bool
	 */
	private function is_checkout_context(): bool {
		if ( is_checkout() ) {
			return true;
		}
		// Store API REST request (e.g. POST /wc/store/v1/checkout).
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			return false !== strpos( $uri, '/wc/store/' );
		}
		return false;
	}

	/**
	 * Whether the currently chosen payment method (from the WC session) is one
	 * that has declared it does not require billing address fields.
	 *
	 * @return bool
	 */
	private function is_no_billing_gateway_selected(): bool {
		$chosen = WC()->session ? WC()->session->get( 'chosen_payment_method', '' ) : '';
		if ( empty( $chosen ) ) {
			return false;
		}
		return in_array( $chosen, $this->get_no_billing_gateway_ids(), true );
	}

	/**
	 * Return the list of payment gateway IDs that don't require billing address fields.
	 *
	 * @return string[]
	 */
	private function get_no_billing_gateway_ids(): array {
		$gateways   = WC_Payment_Gateways::instance()->payment_gateways();
		$no_billing = array();
		foreach ( $gateways as $id => $gateway ) {
			if ( property_exists( $gateway, 'require_billing_fields' ) && false === $gateway->require_billing_fields ) {
				$no_billing[] = $id;
			}
			if ( 'cod' === $id ) {
				$no_billing[] = $id;
			}
		}
		return array_unique( $no_billing );
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
			plugins_url( 'build/wc-remove-billing-address-on-free-checkout-blocks.js', __DIR__ . '/../../../' ),
			array_unique( array_merge( $script_asset['dependencies'], array( 'wc-blocks-data-store', 'wc-settings' ) ) ),
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
	 * Return the list of gateway IDs that don't require billing fields, plus store
	 * address data so the JS can inject valid dummy billing data.
	 *
	 * @see IntegrationInterface::get_script_data()
	 * @return array<string,mixed>
	 */
	public function get_script_data(): array {
		$base_location = wc_get_base_location();
		return array(
			'no_billing_gateways' => $this->get_no_billing_gateway_ids(),
			'store_country'       => $base_location['country'] ?? '',
			'store_state'         => $base_location['state'] ?? '',
			'store_postcode'      => get_option( 'woocommerce_store_postcode', '' ),
		);
	}

	/**
	 * After a Store API order is placed with a no-billing gateway, clear the dummy
	 * billing address fields that were injected client-side to pass validation.
	 *
	 * Preserves billing_first_name, billing_last_name, and billing_email so that
	 * order confirmation emails remain functional.
	 *
	 * @hooked woocommerce_store_api_checkout_order_processed
	 *
	 * @param WC_Order $order Newly placed order object.
	 * @return void
	 */
	public function clear_dummy_billing_for_no_billing_gateway( WC_Order $order ): void {
		if ( ! in_array( $order->get_payment_method(), $this->get_no_billing_gateway_ids(), true ) ) {
			return;
		}
		$order->set_billing_address_1( '' );
		$order->set_billing_address_2( '' );
		$order->set_billing_city( '' );
		$order->set_billing_postcode( '' );
		$order->set_billing_state( '' );
		$order->set_billing_phone( '' );
		$order->save();
	}
}
