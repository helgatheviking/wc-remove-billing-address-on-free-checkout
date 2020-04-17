<?php
/*
 * Plugin Name: Remove WooCommerce billing address for free checkout
 * Plugin URI:  https://github.com/helgatheviking/wc-remove-billing-address-on-free-checkout
 * Description: Remove billing address fields fro checkout when cart total is free.
 * Version: 1.0.0
 * Author: Kathy Darling
 * Author URI: https://kathyisawesome.com 
 * Requires at least: 5.3
 * WC requires at least: 4.0  
 * Tested up to: 5.3.0
 * WC tested up to: 4.1  
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

    if( 0.0 === $cart_total ) {

        if( isset( $fields['billing_address_1'] ) ) unset( $fields['billing_address_1'] );
        if( isset( $fields['billing_address_2'] ) ) unset( $fields['billing_address_2'] );
        if( isset( $fields['billing_city'] ) ) unset( $fields['billing_city'] );
        if( isset( $fields['billing_state'] ) ) unset( $fields['billing_state'] );
        if( isset( $fields['billing_postcode'] ) ) unset( $fields['billing_postcode'] );
        if( isset( $fields['billing_country'] ) ) unset( $fields['billing_country'] );
        if( isset( $fields['billing_phone'] ) ) unset( $fields['billing_phone'] );

    }

    return $fields;
}
add_filter( 'woocommerce_billing_fields', 'kia_remove_billing_address_fields_for_free_checkout' );