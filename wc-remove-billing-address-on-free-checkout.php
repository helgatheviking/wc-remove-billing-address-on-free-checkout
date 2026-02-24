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

namespace KIA\WC_Remove_Billing_Address_On_FreeCheckout;

use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WC_Remove_Billing_Address_On_Free_Checkout;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

(new WC_Remove_Billing_Address_On_Free_Checkout())->register_hooks();

// TODO:
// This should control the address requirement both in context of payment gateway and product type
// Billing = payment gateway
// Shipping = product



// Oh, I get it, why even have a payment gateway when the price is zero and there is no shipping.


// If a product is free, no billing.
// If a product is digital, no shipping
// If a gateway is venmo/bitcoin etc, no billing

// Have a list of checkout fields (run the filter)
// Allow customising which are displaued
// Allow custominsing which are required (the first step to block checkout support)

// When a customer is logged in, and has a previous order with an address, ensure the address is not wiped just because we don't need it for this particular purchase.

// E.g. we don't actually need to know somebody's name if they're downloading software.
//


// Tests need
// A product that does not need shipping
// A product that does need shipping
// A gateway that requires billing address
// A gateway that does not need billing address

// It needs to test that as we switch between gateways, the fields become available / not
