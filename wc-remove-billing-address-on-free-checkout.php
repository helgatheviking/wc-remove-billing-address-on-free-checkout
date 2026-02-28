<?php
/*
 * Plugin Name: Remove WooCommerce billing address for free checkout
 * Plugin URI:  https://github.com/helgatheviking/wc-remove-billing-address-on-free-checkout
 * Description: Remove billing address fields from checkout when cart total is free. Per-gateway settings for which fields to remove or make optional.
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

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

( new WC_Remove_Billing_Address_On_Free_Checkout() )->register_hooks();
