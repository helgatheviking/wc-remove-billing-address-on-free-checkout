<?php
/**
 * Plugin Name:       Development Plugin
 * Description:       Convenience, demo and test helper functions.
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin;

use Alley_Interactive\Autoloader\Autoloader;
use WC_Payment_Gateway;

$actual_plugin_dir = dirname(__DIR__, 2)  . '/wc-remove-billing-address-on-free-checkout';

if(is_readable($actual_plugin_dir . '/vendor/autoload.php')) {
	require_once $actual_plugin_dir . '/vendor/autoload.php';
}

/**
 * TODO check for stray requests: exchange rate query seems to be happening too frequently.
 * https://api-pub.bitfinex.com/v2/tickers?symbols=tBTCUSD
 */

if ( ! defined( 'WPINC' ) ) {
	return;
}

//Autoloader::generate(
//	'KIA\\WC_Remove_Billing_Address_On_Free_Checkout\\Development_Plugin',
//	__DIR__,
//)->register();

require_once __DIR__ . '/class-wp-env.php';

// `wp-env` fixes.
(new WP_Env())->register_hooks();

add_filter( 'woocommerce_payment_gateways', function(array $gateways) {

	$gateways['one'] = new class() extends WC_Payment_Gateway {
		public $method_title = 'Test Gateway 1'; // How it is displayed in admin UI.
		public $title = 'Test Gateway 1'; // How it is displayed at checkout.
		public $id = 'one'; // Unique id.
	};

	$gateways['two'] = new class() extends WC_Payment_Gateway {
		public $method_title = 'Test Gateway 2'; // How it is displayed in admin UI.
		public $title = 'Test Gateway 2'; // How it is displayed at checkout.
		public $id = 'two'; // Unique id.
	};

	return $gateways;
});
