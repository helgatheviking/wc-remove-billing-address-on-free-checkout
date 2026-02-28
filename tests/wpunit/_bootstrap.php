<?php
/**
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

// Ensure WooCommerce is available and ready.
if ( function_exists( 'WC' ) ) {
	WC()->init(); /** @phpstan-ignore method.nonObject */
}
