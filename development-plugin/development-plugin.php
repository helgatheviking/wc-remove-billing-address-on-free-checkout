<?php
/**
 * Plugin Name:       Development Plugin
 * Description:       Convenience, demo and test helper functions.
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin;

use Alley_Interactive\Autoloader\Autoloader;
use WC_Payment_Gateway;

$actual_plugin_dir = dirname( __DIR__, 2 ) . '/wc-remove-billing-address-on-free-checkout';

if ( is_readable( $actual_plugin_dir . '/vendor/autoload.php' ) ) {
	require_once $actual_plugin_dir . '/vendor/autoload.php';
}

if ( ! defined( 'WPINC' ) ) {
	return;
}

// Autoloader::generate(
// 'KIA\\WC_Remove_Billing_Address_On_Free_Checkout\\Development_Plugin',
// __DIR__,
// )->register();

require_once __DIR__ . '/class-wp-env.php';

// `wp-env` fixes.
( new WP_Env() )->register_hooks();

/**
 * I wanted to use Cash On Delivery and Cheque gateways, but they use JavaScript UIs
 */
add_filter(
	'woocommerce_payment_gateways',
	function ( array $gateways ) {

		$gateways['test_gateway_one'] = new class() extends WC_Payment_Gateway {
			public function __construct() {
				$this->update_option( 'enabled', 'yes' );
			}
			public $method_title = 'Test Gateway 1'; // How it is displayed in admin UI.
			public $title        = 'Test Gateway 1'; // How it is displayed at checkout.
			public $id           = 'test_gateway_one'; // Unique id.
			public $description  = 'Test Gateway 1';

			public function process_payment( $order_id ) {
				// Reduce stock levels.
				wc_reduce_stock_levels( $order_id );

				// Remove cart.
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url(),
				);
			}
		};

		$gateways['test_gateway_two'] = new class() extends WC_Payment_Gateway {
			public function __construct() {
				$this->update_option( 'enabled', 'yes' );
			}
			public $method_title           = 'Test Gateway 2'; // How it is displayed in admin UI.
			public $title                  = 'Test Gateway 2'; // How it is displayed at checkout.
			public $id                     = 'test_gateway_two'; // Unique id.
			public $description            = 'Test Gateway 2';
			public $require_billing_fields = false;

			public function process_payment( $order_id ) {
				// Reduce stock levels.
				wc_reduce_stock_levels( $order_id );

				// Remove cart.
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url(),
				);
			}
		};

		$gateways['test_gateway_one']->get_option( 'enabled', 'yes' );
		$gateways['test_gateway_two']->get_option( 'enabled', 'yes' );

		return $gateways;
	}
);
