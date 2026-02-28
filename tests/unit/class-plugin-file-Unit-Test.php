<?php

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout;

use function Patchwork\redefine;
use function Patchwork\relay;

/**
 * Loads the plugin with `require`.
 */
class Plugin_File_Unit_Test extends Unit_Testcase {

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include(): void {

		$plugin_root_dir = codecept_root_dir();

		$called = array();

		redefine(
			'defined',
			function ( $constant_name ) {

				if ( 'ABSPATH' === $constant_name ) {
					return true;
				}

				return relay( func_get_args() );
			}
		);

		redefine(
			array( WC_Remove_Billing_Address_On_Free_Checkout::class, 'register_hooks' ),
			function () use ( &$called ) {
				$called[] = 'WC_Remove_Billing_Address_On_Free_Checkout::register_hooks';
			}
		);

		require codecept_root_dir( 'wc-remove-billing-address-on-free-checkout.php' );

		$this->assertContains( 'WC_Remove_Billing_Address_On_Free_Checkout::register_hooks', $called, json_encode( $called ) ?: 'error printing $called array' );
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include_no_abspath(): void {

		$plugin_root_dir = codecept_root_dir();

		$called = array();

		redefine(
			'defined',
			function ( $constant_name ) {

				if ( 'ABSPATH' === $constant_name ) {
					return false;
				}

				return relay( func_get_args() );
			}
		);

		redefine(
			array( WC_Remove_Billing_Address_On_Free_Checkout::class, 'register_hooks' ),
			function () use ( &$called ) {
				$called[] = 'WC_Remove_Billing_Address_On_Free_Checkout::register_hooks';
			}
		);

		require codecept_root_dir( 'wc-remove-billing-address-on-free-checkout.php' );

		$this->assertNotContains( 'WC_Remove_Billing_Address_On_Free_Checkout::register_hooks', $called, json_encode( $called ) ?: 'error printing $called array' );
	}
}
