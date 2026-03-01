<?php
/**
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\Development_Plugin\Gateways;

/**
 * Test payment gateway that requires billing fields (standard behaviour).
 */
class Test_Gateway_One extends \WC_Payment_Gateway {

	/** @var string How it is displayed in admin UI. */
	public $method_title = 'Test Gateway 1';

	/** @var string How it is displayed at checkout. */
	public $title = 'Test Gateway 1';

	/** @var string Unique id. */
	public $id = 'test_gateway_one';

	/** @var string */
	public $description = 'Test Gateway 1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->update_option( 'enabled', 'yes' );
	}

	public function is_available() {
		return true;
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
