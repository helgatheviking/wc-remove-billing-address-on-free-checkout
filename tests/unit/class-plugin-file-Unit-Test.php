<?php

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout;

use WP_Mock\HookedCallback;
use function Patchwork\redefine;
use function Patchwork\relay;

/**
 * Loads the plugin with `require`.
 */
class Plugin_File_Unit_Test extends Unit_Testcase {

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include_happy_path(): void {

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

		\WP_Mock::expectActionAdded( 'woocommerce_loaded', \WP_Mock\Functions::type( 'callable' ) );

		redefine(
			array( WC_Remove_Billing_Address_On_Free_Checkout::class, 'register_hooks' ),
			function () use ( &$called ) {
				$called[] = 'WC_Remove_Billing_Address_On_Free_Checkout::register_hooks';
			}
		);

		$woocommerce_loaded_callbacks = array();

		redefine(
			array( HookedCallback::class, 'react' ),
			function ( $callback, $priority, $argument_count ) use ( &$woocommerce_loaded_callbacks ) {
				/** @phpstan-ignore-next-line instanceof.alwaysFalse, instanceof.alwaysFalse */
				if ( ( $this instanceof HookedCallback ) && 'woocommerce_loaded' === $this->name ) {
					$woocommerce_loaded_callbacks[] = $callback;
				}
				return relay( func_get_args() );
			}
		);

		require codecept_root_dir( 'wc-remove-billing-address-on-free-checkout.php' );

		foreach ( $woocommerce_loaded_callbacks as $callback ) { /** @phpstan-ignore foreach.emptyArray */
			call_user_func( $callback );
		}

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

		\WP_Mock::expectActionNotAdded( 'woocommerce_loaded', \WP_Mock\Functions::type( 'callable' ) );
	}
}
