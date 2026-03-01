<?php
/**
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Gateways;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WC_Payment_Gateway;

/**
 * Registers Test Gateway 1 with WooCommerce Blocks.
 */
class Test_Gateway_One_Blocks extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var Test_Gateway_One
	 */
	protected $gateway;

	/**
	 * Payment method name. Must match WC_Payment_Gateway::$id.
	 *
	 * @var string
	 */
	protected $name = 'test_gateway_one';

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
		wp_register_script(
			'test-gateway-one-blocks',
			plugins_url( 'js/test-gateway-one.js', __FILE__ ),
			array( 'wc-blocks-registry', 'wp-element' ),
			'1.0.0',
			true
		);

		return array( 'test-gateway-one-blocks' );
	}
}
