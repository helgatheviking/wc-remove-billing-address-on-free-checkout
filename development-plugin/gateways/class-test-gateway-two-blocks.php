<?php
/**
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Gateways;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WC_Payment_Gateway;

/**
 * Registers Test Gateway 2 with WooCommerce Blocks.
 */
class Test_Gateway_Two_Blocks extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var Test_Gateway_Two
	 */
	protected $gateway;

	/**
	 * Payment method name. Must match WC_Payment_Gateway::$id.
	 *
	 * @var string
	 */
	protected $name = 'test_gateway_two';

	/**
	 * Constructor.
	 *
	 * @param WC_Payment_Gateway $gateway The classic gateway instance.
	 */
	public function __construct( WC_Payment_Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize(): void {
		$this->settings = $this->gateway->settings;
	}

	/**
	 * Returns if this payment method should be active.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return true;
		return $this->gateway->is_available();
	}

	/**
	 * Returns data made available to the payment methods script via `getSetting('venmo_data')`.
	 *
	 * @return array{title:string, description:string, supports:array<string>, venmo_icon_url:string}
	 */
	public function get_payment_method_data(): array {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_payment_method_script_handles() {
		$asset_file = require dirname( __DIR__ ) . '/build/test-gateway-two.asset.php';
		wp_register_script(
			'test-gateway-two-blocks',
			plugins_url( 'build/test-gateway-two.js', dirname( __DIR__ ) . '/development-plugin.php' ),
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		return array( 'test-gateway-two-blocks' );
	}
}
