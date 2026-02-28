<?php

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce\Checkout;
use KIA\WC_Remove_Billing_Address_On_Free_Checkout\WPUnit_Testcase;

/**
 * @coversDefaultClass \KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce\Checkout
 * @phpstan-import-type Field from Checkout
 */
class Shortcode_Checkout_Unit_Test extends WPUnit_Testcase {

	private ReflectionMethod $is_checkout_page;

	public function setUp(): void {
		parent::setUp();

		$this->is_checkout_page = new ReflectionMethod( Checkout::class, 'is_checkout_page' );
		$this->is_checkout_page->setAccessible( true );

		$this->reset_cart_checkout_utils_cache();
	}

	protected function tearDown(): void {
		parent::tearDown();

		remove_all_filters( 'wp_doing_ajax' );
		unset( $_REQUEST['wc-ajax'] );

		$this->reset_cart_checkout_utils_cache();
	}

	/**
	 * CartCheckoutUtils::is_checkout_page() caches its result in a static property.
	 * Reset it so each test gets a fresh evaluation.
	 */
	private function reset_cart_checkout_utils_cache(): void {
		$ref = new ReflectionProperty( CartCheckoutUtils::class, 'is_checkout_page' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	private function call_is_checkout_page(): bool {
		return $this->is_checkout_page->invoke( new Checkout() );
	}

	/**
	 * @covers ::is_checkout_page
	 */
	public function test_ajax_wc_ajax_checkout_returns_true(): void {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_REQUEST['wc-ajax'] = 'checkout';

		$this->assertTrue( $this->call_is_checkout_page() );
	}

	/**
	 * @covers ::is_checkout_page
	 */
	public function test_ajax_wc_ajax_update_order_review_returns_true(): void {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_REQUEST['wc-ajax'] = 'update_order_review';

		$this->assertTrue( $this->call_is_checkout_page() );
	}

	/**
	 * When the request is AJAX but wc-ajax is not a checkout action, the method falls
	 * through to CartCheckoutUtils::is_checkout_page().
	 *
	 * @covers ::is_checkout_page
	 */
	public function test_ajax_unrelated_wc_ajax_not_on_checkout_page_returns_false(): void {
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_REQUEST['wc-ajax'] = 'get_refreshed_fragments';
		$this->go_to( home_url( '/' ) );

		$this->assertFalse( $this->call_is_checkout_page() );
	}

	/**
	 * @covers ::is_checkout_page
	 */
	public function test_non_ajax_on_checkout_page_returns_true(): void {
		$this->go_to( wc_get_checkout_url() );

		$this->assertTrue( $this->call_is_checkout_page() );
	}

	/**
	 * @covers ::is_checkout_page
	 */
	public function test_non_ajax_not_on_checkout_page_returns_false(): void {
		$this->go_to( home_url( '/' ) );

		$this->assertFalse( $this->call_is_checkout_page() );
	}
}
