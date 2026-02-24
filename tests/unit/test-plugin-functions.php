<?php

namespace WC_Remove_Billing;

/**
 * Test the plugin's main function.
 * Since this is a simple single-file plugin, we'll test the main function logic.
 */
class Test_Plugin_Functions extends Unit_Testcase {

	/**
	 * Test the plugin's main function exists.
	 */
	public function test_function_exists(): void {
		// Include the plugin file
		require_once codecept_root_dir( 'wc-remove-billing-address-on-free-checkout.php' );

		// Verify the function exists
		$this->assertTrue( function_exists( 'remove_billing_address_fields_for_free_checkout' ) );
	}

	/**
	 * Test the function removes fields when cart total is zero and no shipping is needed.
	 */
	public function test_removes_fields_for_free_cart(): void {
		require_once codecept_root_dir( 'wc-remove-billing-address-on-free-checkout.php' );

		// Mock WC cart
		\WP_Mock::userFunction( 'WC', [
			'return' => (object) [
				'cart' => (object) [
					'get_total' => function( $context = 'view' ) {
						return '0.00';
					},
					'needs_shipping' => function() {
						return false;
					}
				]
			]
		] );

		\WP_Mock::userFunction( 'apply_filters', [
			'args' => [ 'wc_billing_fields_to_remove_for_free_checkout', \WP_Mock\Functions::type( 'array' ) ],
			'return_arg' => 1
		] );

		$input_fields = [
			'billing_first_name' => [],
			'billing_last_name' => [],
			'billing_email' => [],
			'billing_address_1' => [],
			'billing_city' => [],
			'billing_state' => [],
			'billing_postcode' => [],
			'billing_country' => [],
			'billing_phone' => []
		];

		$result = remove_billing_address_fields_for_free_checkout( $input_fields );

		// Address fields should be removed
		$this->assertArrayNotHasKey( 'billing_address_1', $result );
		$this->assertArrayNotHasKey( 'billing_city', $result );
		$this->assertArrayNotHasKey( 'billing_state', $result );
		$this->assertArrayNotHasKey( 'billing_postcode', $result );
		$this->assertArrayNotHasKey( 'billing_country', $result );
		$this->assertArrayNotHasKey( 'billing_phone', $result );

		// Name and email should remain
		$this->assertArrayHasKey( 'billing_first_name', $result );
		$this->assertArrayHasKey( 'billing_last_name', $result );
		$this->assertArrayHasKey( 'billing_email', $result );
	}

	/**
	 * Test the function does not remove fields when cart total is greater than zero.
	 */
	public function test_keeps_fields_for_paid_cart(): void {
		require_once codecept_root_dir( 'wc-remove-billing-address-on-free-checkout.php' );

		// Mock WC cart with paid items
		\WP_Mock::userFunction( 'WC', [
			'return' => (object) [
				'cart' => (object) [
					'get_total' => function( $context = 'view' ) {
						return '19.99';
					},
					'needs_shipping' => function() {
						return false;
					}
				]
			]
		] );

		$input_fields = [
			'billing_first_name' => [],
			'billing_last_name' => [],
			'billing_email' => [],
			'billing_address_1' => [],
			'billing_city' => [],
			'billing_state' => [],
			'billing_postcode' => [],
			'billing_country' => [],
			'billing_phone' => []
		];

		$result = remove_billing_address_fields_for_free_checkout( $input_fields );

		// All fields should remain for paid cart
		$this->assertArrayHasKey( 'billing_address_1', $result );
		$this->assertArrayHasKey( 'billing_city', $result );
		$this->assertArrayHasKey( 'billing_state', $result );
		$this->assertArrayHasKey( 'billing_postcode', $result );
		$this->assertArrayHasKey( 'billing_country', $result );
		$this->assertArrayHasKey( 'billing_phone', $result );
		$this->assertArrayHasKey( 'billing_first_name', $result );
		$this->assertArrayHasKey( 'billing_last_name', $result );
		$this->assertArrayHasKey( 'billing_email', $result );
	}

	/**
	 * Test the function does not remove fields when shipping is needed.
	 */
	public function test_keeps_fields_when_shipping_needed(): void {
		require_once codecept_root_dir( 'wc-remove-billing-address-on-free-checkout.php' );

		// Mock WC cart that needs shipping
		\WP_Mock::userFunction( 'WC', [
			'return' => (object) [
				'cart' => (object) [
					'get_total' => function( $context = 'view' ) {
						return '0.00';
					},
					'needs_shipping' => function() {
						return true;
					}
				]
			]
		] );

		$input_fields = [
			'billing_first_name' => [],
			'billing_last_name' => [],
			'billing_email' => [],
			'billing_address_1' => [],
			'billing_city' => [],
			'billing_state' => [],
			'billing_postcode' => [],
			'billing_country' => [],
			'billing_phone' => []
		];

		$result = remove_billing_address_fields_for_free_checkout( $input_fields );

		// All fields should remain when shipping is needed
		$this->assertArrayHasKey( 'billing_address_1', $result );
		$this->assertArrayHasKey( 'billing_city', $result );
		$this->assertArrayHasKey( 'billing_state', $result );
		$this->assertArrayHasKey( 'billing_postcode', $result );
		$this->assertArrayHasKey( 'billing_country', $result );
		$this->assertArrayHasKey( 'billing_phone', $result );
		$this->assertArrayHasKey( 'billing_first_name', $result );
		$this->assertArrayHasKey( 'billing_last_name', $result );
		$this->assertArrayHasKey( 'billing_email', $result );
	}
}
