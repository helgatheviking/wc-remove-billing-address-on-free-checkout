<?php

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce;

use WC_Payment_Gateways;

class Checkout {

	/**
	 * Default fields to remove (the original plugin behavior).
	 *
	 * Each gateway settings page allows customising which fields are removed. If there has been no customisation
	 * done, use this list.
	 *
	 * @return string[]
	 */
	protected function get_default_billing_fields_to_remove_for_free_checkout(): array {
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
	 * List of default shipping fields.
	 *
	 * TODO: What does this look like when plugins add custom fields. Usually that is for billing – e.g. EU tax number.
	 *
	 * @return string[]
	 */
	protected function get_default_fields_unnecessary_when_no_shipping(): array {
		return array(
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_state',
			'shipping_postcode',
			'shipping_country',
			'shipping_phone',
		);
	}

	/**
	 * Get the chosen payment gateway at checkout.
	 *
	 * @return string|null The gateway ID, or null if not determinable.
	 */
	protected function get_chosen_payment_gateway(): ?string {
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
	 * Get the (saved) fields to remove for a specific gateway.
	 *
	 * Falls back to the default list if no per-gateway settings are saved.
	 *
	 * @param string $gateway_id The payment gateway ID.
	 * @return string[] Field keys to remove.
	 */
	protected function get_fields_to_remove_for_gateway( string $gateway_id ): array {
		$gateway = $this->get_gateway_instance( $gateway_id );

		if ( ! $gateway ) {
			return $this->get_default_billing_fields_to_remove_for_free_checkout();
		}

		$saved = $gateway->get_option( 'kia_free_checkout_remove_fields' );

		if ( ! is_array( $saved ) || empty( $saved ) ) {
			return $this->get_default_billing_fields_to_remove_for_free_checkout();
		}

		return $saved;
	}

	/**
	 * Get a WC_Payment_Gateway instance by ID.
	 *
	 * @param string $gateway_id The gateway ID.
	 * @return \WC_Payment_Gateway|null
	 */
	protected function get_gateway_instance( string $gateway_id ): ?\WC_Payment_Gateway {
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
	public function remove_billing_address_fields_for_free_checkout( $fields ) {

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

		$gateway_id = $this->get_chosen_payment_gateway();

		if ( $gateway_id ) {
			$fields_to_remove = $this->get_fields_to_remove_for_gateway( $gateway_id );
		} else {
			$fields_to_remove = $this->get_default_billing_fields_to_remove_for_free_checkout();
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
}
