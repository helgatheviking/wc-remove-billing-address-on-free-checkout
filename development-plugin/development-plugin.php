<?php
/**
 * Plugin Name:       Development Plugin
 * Description:       Convenience, demo and test helper functions.
 *
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin;

use Alley_Interactive\Autoloader\Autoloader;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Rest\WooCommerce_Settings;
use WC_Payment_Gateway;

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

/**
 * I wanted to use Cash On Delivery and Cheque gateways, but they use JavaScript UIs
 */
add_filter(
	'woocommerce_payment_gateways',
	function ( array $gateways ) {

		$gateways['test_gateway_one'] = new class() extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->update_option( 'enabled', 'yes' );
			}
			/** @var string How it is displayed in admin UI. */
			public $method_title = 'Test Gateway 1';
			/** @var string How it is displayed at checkout. */
			public $title = 'Test Gateway 1';
			/** @var string Unique id. */
			public $id = 'test_gateway_one';
			/** @var string I'm not sure where this gets displayed. */
			public $description = 'Test Gateway 1';

			/**
			 * @inheritDoc
			 *
			 * @see \WC_Checkout::process_checkout()
			 * @see \WC_Checkout::create_order()
			 *
			 * @param int $order_id The id of the already created WC_Order object.
			 */
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
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->update_option( 'enabled', 'yes' );
			}
			/** @var string How it is displayed at checkout. */
			public $title = 'Test Gateway 2';
			/** @var string Unique id */
			public $id = 'test_gateway_two';
			/** @var string I'm not sure where this gets displayed. */
			public $description = 'Test Gateway 2';
			/** @var bool Our custom property. Open to change. */
			public bool $require_billing_fields = false;

			/**
			 * @inheritDoc
			 *
			 * @see \WC_Checkout::process_checkout()
			 * @see \WC_Checkout::create_order()
			 *
			 * @param int $order_id The id of the already created WC_Order object.
			 */
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
