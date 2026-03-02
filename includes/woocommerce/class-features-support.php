<?php
/**
 * Declare compatability with WooCommerce features, i.e. Cart & Checkout Blocks.
 *
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Declare compatibility with Cart & Checkout Blocks.
 * And also High Performance Order Storage because we don't manipulate orders at all.
 *
 * @see FeaturesController
 */
class Features_Support {

	/**
	 * Register compatibility with WooCommerce Blocks cart and checkout.
	 *
	 * @hooked before_woocommerce_init
	 */
	public function declare_cart_checkout_blocks_compatibility(): void {
		if ( ! class_exists( FeaturesUtil::class ) ) {
			return;
		}

		FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', 'wc-remove-billing-address-on-free-checkout', true );
	}

	/**
	 * Register compatibility with HPOS.
	 *
	 * We do not do anything with orders.
	 *
	 * @hooked before_woocommerce_init
	 */
	public function declare_hpos_compatibility(): void {
		if ( ! class_exists( FeaturesUtil::class ) ) {
			return;
		}

		FeaturesUtil::declare_compatibility( 'custom_order_tables', 'wc-remove-billing-address-on-free-checkout', true );
	}
}
