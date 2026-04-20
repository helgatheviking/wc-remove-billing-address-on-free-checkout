<?php
/*
 * Plugin Name: Remove WooCommerce billing address for free checkout
 * Plugin URI:  https://github.com/helgatheviking/wc-remove-billing-address-on-free-checkout
 * Description: Remove billing address fields fro checkout when cart total is free.
 * Version: 1.0.1
 * Author: Kathy Darling
 * Author URI: https://kathyisawesome.com 
 * Requires at least: 5.3
 * Tested up to: 5.8.2
 * WC requires at least: 5.5.0
 * WC tested up to: 8.6.0
 *
 * Copyright: © 2024 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

namespace HelgaTheViking\RemoveBillingAddressForFreeCheckout;

defined( 'ABSPATH' ) || exit;

/**
 * Declare WooCommerce Features Compatibility
 */
add_action( 'before_woocommerce_init', function() {
	if ( ! class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		return;
	}

	// High Performance Order Storage (HPOS) Compatibility
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_basename( __FILE__ ), true );

	// Cart/Checkout Blocks compatibility - Currently False.
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', plugin_basename( __FILE__ ), true );
} );


/**
 * Remove required billing fields if Order total is 0.00
 * 
 * @since 2.0.0
 *
 *
 * @param array   $fields    The billing fields
 * @return array
 */
function remove_fields( $fields ) {

   if ( needs_billing_address() ) {
		return $fields;
	}

	foreach ( fields_to_remove() as $field ) {
		if( isset( $fields[$field] ) ) unset( $fields[$field] );
	}

	return $fields;
}
add_filter( 'woocommerce_billing_fields', __NAMESPACE__ . '\\remove_fields' );
/**
 * Does this cart need a billing address?
 * 
 * @since 2.0.0
 *
 * @return boolean
 */
function needs_billing_address() {

	$cart = wc()->cart;

	// Only remove fields if we're operating on a cart.
	if ( ! $cart ) {
		return true;
	}

	// Only remove fields if we're dealing with a virtual cart.
	if ( $cart->needs_shipping() ) {
		return true;
	}

	// Only remove fields if we're dealing with a free cart.
	if ( $cart->needs_payment() ) {
		return true;
	}

	return false;
}

/**
 * Billing fields to remove
 *
 * @since 2.0.0
 * 
 * @return array An array of billing fields to remove
 */
function fields_to_remove() {
	/**
	 * Fields to remove if Order total is 0.00
	 *
	 * @since 1.0.1
	 * @param array   $fields    The billing fields we are removing
	 * @return array
	 */
	return (array) apply_filters( 'wc_billing_fields_to_remove_for_free_checkout', array( 
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
		'billing_country',
		'billing_phone',
	) );
}