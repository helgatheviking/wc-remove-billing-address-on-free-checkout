<?php
/*
 * Plugin Name: Remove WooCommerce billing address for free checkout
 * Plugin URI:  https://github.com/helgatheviking/wc-remove-billing-address-on-free-checkout
 * Description: Remove billing address fields from checkout when cart total is free. Per-gateway settings for which fields to remove.
 * Version: 2.0.0
 * Author: Kathy Darling
 * Author URI: https://kathyisawesome.com
 * Requires at least: 5.3
 * Tested up to: 6.7
 * WC requires at least: 7.0.0
 * WC tested up to: 9.6.0
 * Requires PHP: 8.1
 *
 * Copyright: © 2020 Kathy Darling; 2026 Brian Henry.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * All billing fields that can be controlled per-gateway.
 *
 * @return array<string, string> Field key => human-readable label.
 */
function kia_get_all_billing_field_options(): array {
	return array(
		'billing_first_name' => __( 'First name', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_last_name'  => __( 'Last name', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_company'    => __( 'Company', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_address_1'  => __( 'Address line 1', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_address_2'  => __( 'Address line 2', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_city'       => __( 'City', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_state'      => __( 'State', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_postcode'   => __( 'Postcode / ZIP', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_country'    => __( 'Country', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_phone'      => __( 'Phone', 'wc-remove-billing-address-on-free-checkout' ),
		'billing_email'      => __( 'Email address', 'wc-remove-billing-address-on-free-checkout' ),
	);
}

/**
 * Default fields to remove (the original plugin behavior).
 *
 * @return string[]
 */
function kia_get_default_fields_to_remove(): array {
	return array(
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
		'billing_country',
		'billing_phone',
	);
}

/**
 * Get the chosen payment gateway at checkout.
 *
 * @return string|null The gateway ID, or null if not determinable.
 */
function kia_get_chosen_payment_gateway(): ?string {
	if ( ! WC()->session ) {
		return null;
	}

	$chosen = WC()->session->get( 'chosen_payment_method' );

	// Also check POST data (AJAX checkout updates send it here).
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $chosen ) && ! empty( $_POST['payment_method'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$chosen = sanitize_text_field( wp_unslash( $_POST['payment_method'] ) );
	}

	return $chosen ?: null;
}

/**
 * Get the fields to remove for a specific gateway.
 *
 * Falls back to the default list if no per-gateway settings are saved.
 *
 * @param string $gateway_id The payment gateway ID.
 * @return string[] Field keys to remove.
 */
function kia_get_fields_to_remove_for_gateway( string $gateway_id ): array {
	$gateway = kia_get_gateway_instance( $gateway_id );

	if ( ! $gateway ) {
		return kia_get_default_fields_to_remove();
	}

	$saved = $gateway->get_option( 'kia_free_checkout_remove_fields' );

	if ( ! is_array( $saved ) || empty( $saved ) ) {
		return kia_get_default_fields_to_remove();
	}

	return $saved;
}

/**
 * Get a WC_Payment_Gateway instance by ID.
 *
 * @param string $gateway_id The gateway ID.
 * @return \WC_Payment_Gateway|null
 */
function kia_get_gateway_instance( string $gateway_id ): ?\WC_Payment_Gateway {
	$gateways = WC_Payment_Gateways::instance()->payment_gateways();
	return $gateways[ $gateway_id ] ?? null;
}

/**
 * Remove billing fields at checkout based on gateway-specific settings.
 *
 * If the cart is free and no shipping is needed, remove the fields configured
 * for the chosen payment gateway. Falls back to defaults if no per-gateway
 * settings exist.
 *
 * @hooked woocommerce_billing_fields
 *
 * @param array $fields The billing fields.
 * @return array
 */
function kia_remove_billing_address_fields_for_free_checkout( $fields ) {

	if ( is_admin() && ! wp_doing_ajax() ) {
		return $fields;
	}

	if ( ! WC()->cart ) {
		return $fields;
	}

	$cart_total = floatval( WC()->cart->get_total( 'edit' ) );

	if ( WC()->cart->needs_shipping() || 0.0 !== $cart_total ) {
		return $fields;
	}

	$gateway_id = kia_get_chosen_payment_gateway();

	if ( $gateway_id ) {
		$fields_to_remove = kia_get_fields_to_remove_for_gateway( $gateway_id );
	} else {
		$fields_to_remove = kia_get_default_fields_to_remove();
	}

	/**
	 * Filter the fields to remove for free checkout.
	 *
	 * @since 1.0.1
	 * @param string[] $fields_to_remove The billing field keys to remove.
	 * @param string|null $gateway_id The chosen payment gateway ID.
	 * @return string[]
	 */
	$fields_to_remove = (array) apply_filters(
		'wc_billing_fields_to_remove_for_free_checkout',
		$fields_to_remove,
		$gateway_id
	);

	foreach ( $fields_to_remove as $field ) {
		if ( isset( $fields[ $field ] ) ) {
			unset( $fields[ $field ] );
		}
	}

	return $fields;
}
add_filter( 'woocommerce_billing_fields', 'kia_remove_billing_address_fields_for_free_checkout' );

/**
 * Register the settings fields on each payment gateway's settings page.
 *
 * Hooks into `woocommerce_after_register_post_type` (runs after gateways are loaded)
 * and adds a `woocommerce_settings_api_form_fields_{$gateway_id}` filter for each.
 *
 * @hooked woocommerce_after_register_post_type
 */
function kia_register_gateway_settings(): void {
	$gateway_ids = array_keys( WC_Payment_Gateways::instance()->payment_gateways() );

	foreach ( $gateway_ids as $gateway_id ) {
		add_filter(
			"woocommerce_settings_api_form_fields_{$gateway_id}",
			'kia_add_billing_field_settings',
			20,
			1
		);
	}
}
add_action( 'woocommerce_after_register_post_type', 'kia_register_gateway_settings' );

/**
 * Add the per-gateway billing field removal settings to a gateway's settings page.
 *
 * Adds a multiselect field where the admin can choose which billing fields to
 * remove when checkout is free and this gateway is active.
 *
 * @param array<string, array<string, mixed>> $form_fields The gateway's existing settings.
 * @return array<string, array<string, mixed>>
 */
function kia_add_billing_field_settings( array $form_fields ): array {

	$field_options = kia_get_all_billing_field_options();

	$form_fields['kia_free_checkout_section'] = array(
		'title'       => __( 'Free Checkout Billing Fields', 'wc-remove-billing-address-on-free-checkout' ),
		'type'        => 'title',
		'description' => __( 'Choose which billing fields to remove when the checkout total is $0.00 and no shipping is required. If none are selected, the default fields (address, city, state, zip, country, phone) will be removed.', 'wc-remove-billing-address-on-free-checkout' ),
	);

	$form_fields['kia_free_checkout_remove_fields'] = array(
		'title'       => __( 'Remove these billing fields on free checkout', 'wc-remove-billing-address-on-free-checkout' ),
		'type'        => 'multiselect',
		'class'       => 'wc-enhanced-select',
		'css'         => 'width: 400px;',
		'description' => __( 'Selected fields will be hidden when the cart total is free and no shipping is needed.', 'wc-remove-billing-address-on-free-checkout' ),
		'desc_tip'    => true,
		'options'     => $field_options,
		'default'     => kia_get_default_fields_to_remove(),
	);

	return $form_fields;
}
