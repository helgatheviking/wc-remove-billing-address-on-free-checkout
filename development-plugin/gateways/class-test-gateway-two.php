<?php
/**
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Gateways;

use WC_Payment_Gateway;

/**
 * Test payment gateway that does NOT require billing fields.
 */
class Test_Gateway_Two extends WC_Payment_Gateway {

	/** @var string How it is displayed in admin UI. */
	public $method_title = 'Test Gateway 2';

	/** @var string How it is displayed at checkout. */
	public $title = 'Test Gateway 2';

	/** @var string Unique id. */
	public $id = 'test_gateway_two';

	/** @var string */
	public $description = 'Test Gateway 2';

	/** @var bool Our custom property indicating billing fields are not required. */
	public bool $require_billing_fields = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->update_option( 'enabled', 'yes' );
	}

	/**
	 * @inheritDoc
	 *
	 * @param int $order_id The id of the already created WC_Order object.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		wc_reduce_stock_levels( $order_id );
		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url(),
		);
	}
}
