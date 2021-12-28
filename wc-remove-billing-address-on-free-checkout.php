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
 * WC tested up to: 6.0.0
 *
 * Copyright: © 2020 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */
defined( 'ABSPATH' ) || exit;

/**
 * Remove required billing fields if Order total is 0.00
 *
 *
 * @param array   $fields    The billing fields
 * @return array
 */
function kia_remove_billing_address_fields_for_free_checkout( $fields ) { 

    $cart_total = floatval( WC()->cart->get_total( 'edit' ) );

    if( ! WC()->cart->needs_shipping() && 0.0 === $cart_total ) {

        /**
         * Fields to remove if Order total is 0.00
         *
         * @since 1.0.1
         * @param array   $fields    The billing fields we are removing
         * @return array
         */
        $fields_to_remove = (array) apply_filters( 'wc_billing_fields_to_remove_for_free_checkout', array( 
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
            'billing_phone',
        ) );

        foreach ( $fields_to_remove as $field ) {
            if( isset( $fields[$field] ) ) unset( $fields[$field] );
        }

    }

    return $fields;
}
add_filter( 'woocommerce_billing_fields', 'kia_remove_billing_address_fields_for_free_checkout' );
