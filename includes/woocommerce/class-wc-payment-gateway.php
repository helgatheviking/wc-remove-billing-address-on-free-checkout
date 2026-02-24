<?php

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce;


use WC_Payment_Gateways;

class WC_Payment_Gateway {

	/**
	 * All billing fields that can be controlled per-gateway.
	 *
	 * @return array<string, string> Field key => human-readable label.
	 */
	protected function get_all_billing_field_options(): array {
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
	protected function get_default_fields_to_remove(): array {
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
	 * Register the settings fields on each payment gateway's settings page.
	 *
	 * Hooks into `woocommerce_after_register_post_type` (runs after gateways are loaded)
	 * and adds a `woocommerce_settings_api_form_fields_{$gateway_id}` filter for each.
	 *
	 * @hooked woocommerce_after_register_post_type
	 */
	public function register_gateway_settings(): void {
		$gateway_ids = array_keys( WC_Payment_Gateways::instance()->payment_gateways() );

		foreach ( $gateway_ids as $gateway_id ) {
			add_filter(
				"woocommerce_settings_api_form_fields_{$gateway_id}",
				arraY($this,'add_billing_field_settings'),
				20,
				1
			);
		}
	}

	/**
	 * Add the per-gateway billing field removal settings to a gateway's settings page.
	 *
	 * Adds a multiselect field where the admin can choose which billing fields to
	 * remove when checkout is free and this gateway is active.
	 *
	 * @hooked woocommerce_settings_api_form_fields_{$gateway_id}
	 * @see self::register_gateway_settings()
	 *
	 * @param array<string, array<string, mixed>> $form_fields The gateway's existing settings.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_billing_field_settings( array $form_fields ): array {

		$field_options = $this->get_all_billing_field_options();

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
			'default'     => $this->get_default_fields_to_remove(),
		);

		return $form_fields;
	}

}
