<?php
/**
 * Hook the plugin functions to WooCommerce's actions and filters.
 *
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout;

use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce\Block_Checkout;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce\Checkout;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce\Countries;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce\Features_Support;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce\WC_Payment_Gateway;

/**
 * Call WordPress `add_action()` and `add_filter()`; check can we return early if possible.
 */
class WC_Remove_Billing_Address_On_Free_Checkout {

	/**
	 * Public method to add all actions and filters.
	 *
	 * @see wc-remove-billing-address-on-free-checkout.php
	 * @hooked woocommerce_loaded
	 */
	public function register_hooks(): void {
		$this->define_payment_gateway_hooks();
		$this->define_checkout_hooks();
		$this->define_block_checkout_hooks();
		$this->define_woocommerce_features_hooks();
	}

	/**
	 * Add hooks to add the settings in the admin UI.
	 */
	protected function define_payment_gateway_hooks(): void {

		// TODO: This may need to be changed for REST.
		if ( ! is_admin() ) {
			return;
		}

		$wc_payment_gateway = new WC_Payment_Gateway();

		add_action( 'woocommerce_after_register_post_type', array( $wc_payment_gateway, 'register_gateway_settings' ) );
	}

	/**
	 * Register the Block_Checkout integration with WooCommerce Blocks and add
	 * locale filters that must run for all request types (including Store API).
	 *
	 * BlockTypesController is not initialized for Store API requests, so we cannot
	 * rely on Block_Checkout::initialize() being called. Register locale filters
	 * here to ensure server-side validation respects the hidden/not-required state.
	 */
	protected function define_block_checkout_hooks(): void {
		$block_checkout = new Block_Checkout();

		add_action( 'woocommerce_blocks_checkout_block_registration', array( $block_checkout, 'register_integration' ) );

		// Hide billing address fields for free + no-shipping carts.
		// Registered here (not in Block_Checkout::initialize) so they fire for
		// Store API requests, where BlockTypesController is skipped.
		add_filter( 'woocommerce_get_country_locale', array( $block_checkout, 'hide_address_fields_for_free_virtual_cart' ) );
		add_filter( 'woocommerce_get_country_locale_default', array( $block_checkout, 'hide_address_fields_default_locale_for_free_virtual_cart' ) );
	}

	/**
	 * Add hooks to manipulate the fields displayed at checkout.
	 */
	protected function define_checkout_hooks(): void {

		$checkout = new Checkout();

		add_filter( 'woocommerce_billing_fields', array( $checkout, 'remove_billing_address_fields_for_free_checkout' ) );
		add_filter( 'woocommerce_checkout_fields', array( $checkout, 'remove_shipping_fields_for_virtual_products' ) );

		add_action( 'wp_enqueue_scripts', array( $checkout, 'enqueue_scripts' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $checkout, 'rerender_billing_fields' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $checkout, 'rerender_shipping_fields' ) );

		add_filter( 'woocommerce_ship_to_different_address_checked', array( $checkout, 'force_display_shipping_form' ), 10, 1 );

		add_filter( 'do_shortcode_tag', array( $checkout, 'set_shortcode_fragment_title' ), 10, 4 );
		add_filter( 'do_shortcode_tag', array( $checkout, 'set_shortcode_ship_to_different_address' ), 10, 4 );
	}

	/**
	 * Add hooks to confirm with WooCommerce that we support the modern (in 2026) features.
	 */
	protected function define_woocommerce_features_hooks(): void {

		$features_support = new Features_Support();

		add_action( 'before_woocommerce_init', array( $features_support, 'declare_cart_checkout_blocks_compatibility' ) );
		add_action( 'before_woocommerce_init', array( $features_support, 'declare_hpos_compatibility' ) );
	}
}
