<?php

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout;

use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce\Checkout;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce\WC_Payment_Gateway;

class WC_Remove_Billing_Address_On_Free_Checkout {

	public function register_hooks(): void {
		$this->define_payment_gateway_hooks();
		$this->define_checkout_hooks();



	}


	protected function define_payment_gateway_hooks(): void {

		$wc_payment_gateway = new WC_Payment_Gateway();

		add_action( 'woocommerce_after_register_post_type', array($wc_payment_gateway, 'register_gateway_settings' ) );
	}

	protected function define_checkout_hooks(): void {

		$checkout = new Checkout();

		add_filter( 'woocommerce_billing_fields', array( $checkout,'remove_billing_address_fields_for_free_checkout' ) );
		add_filter( 'woocommerce_checkout_fields', array( $checkout, 'remove_shipping_fields_for_virtual_products' ) );


		add_action('wp_enqueue_scripts', array( $checkout, 'enqueue_scripts' ));
		add_filter( 'woocommerce_update_order_review_fragments', array( $checkout, 'rerender_billing_fields') );
		add_filter( 'woocommerce_checkout_fields', array( $checkout, 'remove_billing_fields_per_gateway') );

	}

}
