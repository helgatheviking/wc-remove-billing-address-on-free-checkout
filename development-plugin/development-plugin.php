<?php
/**
 * Plugin Name:       Development Plugin
 * Description:       Convenience, demo and test helper functions.
 *
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin;

use Alley_Interactive\Autoloader\Autoloader;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Gateways\Test_Gateway_One;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Gateways\Test_Gateway_One_Blocks;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Gateways\Test_Gateway_Two;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Gateways\Test_Gateway_Two_Blocks;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Rest\WooCommerce_Settings;

$actual_plugin_dir = dirname( __DIR__, 1 ) . '/wc-remove-billing-address-on-free-checkout';
$autoloader_paths  = array(
	$actual_plugin_dir . '/vendor/autoload.php',
);
foreach ( $autoloader_paths as $autoloader_path ) {
	if ( is_readable( $autoloader_path ) ) {
		require_once $autoloader_path;
	}
}

if ( ! defined( 'WPINC' ) ) {
	return;
}

Autoloader::generate( 'KIA\\WC_Remove_Billing_Address_On_Free_Checkout\\Development_Plugin', __DIR__, )->register();

require_once __DIR__ . '/class-wp-env.php';

// `wp-env` fixes.
( new WP_Env() )->register_hooks();
( new WooCommerce_Settings() )->register_hooks();

add_action( 'woocommerce_loaded', function() {

	$test_gateways = array();
	$test_gateways['test_gateway_one'] = new Test_Gateway_One();
	$test_gateways['test_gateway_two'] = new Test_Gateway_Two();

	/**
	 * Register the test gateways with the classic (shortcode) checkout.
	 */
	add_filter(
		'woocommerce_payment_gateways',
		function ( array $gateways ) use ($test_gateways ){
			$gateways[] = $test_gateways['test_gateway_one'];
			$gateways[] = $test_gateways['test_gateway_two'];
			return $gateways;
		}
	);

	/**
	 * Register the test gateways with the WooCommerce Blocks checkout.
	 */
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function ( $registry ) use ( $test_gateways ) {
			$registry->register( new Test_Gateway_One_Blocks($test_gateways['test_gateway_one']) );
			$registry->register( new Test_Gateway_Two_Blocks($test_gateways['test_gateway_two']) );
		}
	);

});
