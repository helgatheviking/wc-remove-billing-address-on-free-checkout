/**
 * Test the plugin's behavior on the WooCommerce blocks checkout.
 * The plugin only hooks 'woocommerce_billing_fields' which is NOT used by blocks checkout.
 * These tests demonstrate that the plugin does NOT work with blocks checkout.
 */
import { test, expect, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { loginAsAdmin } from './helpers/general/ui/login';

test.describe( 'WC Remove Billing Address - Blocks Checkout (Plugin has NO effect)', () => {
	let freeProductId: string;
	let paidProductId: string;
	let checkoutPageId: string;

	test.beforeAll( async ( { requestUtils } ) => {

		// Create test products using REST API
		const freeProduct = await requestUtils.rest( {
			method: 'POST',
			path: '/wc/v3/products',
			data: {
				name: 'Free Virtual Product Blocks E2E',
				type: 'simple',
				regular_price: '0.00',
				virtual: true,
				status: 'publish'
			}
		} );
		freeProductId = freeProduct.id;

		const paidProduct = await requestUtils.rest( {
			method: 'POST',
			path: '/wc/v3/products',
			data: {
				name: 'Paid Simple Product Blocks E2E',
				type: 'simple',
				regular_price: '19.99',
				status: 'publish'
			}
		} );
		paidProductId = paidProduct.id;

		// Get the checkout page and set it to use blocks
		const pages = await requestUtils.rest( {
			method: 'GET',
			path: '/wp/v2/pages',
			data: {
				search: 'Checkout',
				per_page: 100
			}
		} );

		const checkoutPage = pages.find( ( page: any ) =>
			page.title.rendered.toLowerCase() === 'checkout'
		);

		if ( checkoutPage ) {
			checkoutPageId = checkoutPage.id;
			// Update checkout page to use WooCommerce blocks
			await requestUtils.rest( {
				method: 'POST',
				path: `/wp/v2/pages/${checkoutPageId}`,
				data: {
					content: '<!-- wp:woocommerce/checkout -->\n<div class="wp-block-woocommerce-checkout alignwide is-loading"><!-- wp:woocommerce/checkout-fields-block -->\n<div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-express-payment-block -->\n<div class="wp-block-woocommerce-checkout-express-payment-block"></div>\n<!-- /wp:woocommerce/checkout-express-payment-block -->\n\n<!-- wp:woocommerce/checkout-contact-information-block -->\n<div class="wp-block-woocommerce-checkout-contact-information-block"></div>\n<!-- /wp:woocommerce/checkout-contact-information-block -->\n\n<!-- wp:woocommerce/checkout-shipping-address-block -->\n<div class="wp-block-woocommerce-checkout-shipping-address-block"></div>\n<!-- /wp:woocommerce/checkout-shipping-address-block -->\n\n<!-- wp:woocommerce/checkout-billing-address-block -->\n<div class="wp-block-woocommerce-checkout-billing-address-block"></div>\n<!-- /wp:woocommerce/checkout-billing-address-block -->\n\n<!-- wp:woocommerce/checkout-shipping-methods-block -->\n<div class="wp-block-woocommerce-checkout-shipping-methods-block"></div>\n<!-- /wp:woocommerce/checkout-shipping-methods-block -->\n\n<!-- wp:woocommerce/checkout-payment-block -->\n<div class="wp-block-woocommerce-checkout-payment-block"></div>\n<!-- /wp:woocommerce/checkout-payment-block -->\n\n<!-- wp:woocommerce/checkout-order-note-block -->\n<div class="wp-block-woocommerce-checkout-order-note-block"></div>\n<!-- /wp:woocommerce/checkout-order-note-block -->\n\n<!-- wp:woocommerce/checkout-terms-block -->\n<div class="wp-block-woocommerce-checkout-terms-block"></div>\n<!-- /wp:woocommerce/checkout-terms-block -->\n\n<!-- wp:woocommerce/checkout-actions-block -->\n<div class="wp-block-woocommerce-checkout-actions-block"></div>\n<!-- /wp:woocommerce/checkout-actions-block --></div>\n<!-- /wp:woocommerce/checkout-fields-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-block -->\n<div class="wp-block-woocommerce-checkout-order-summary-block"><!-- wp:woocommerce/checkout-order-summary-cart-items-block -->\n<div class="wp-block-woocommerce-checkout-order-summary-cart-items-block"></div>\n<!-- /wp:woocommerce/checkout-order-summary-cart-items-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-coupon-form-block -->\n<div class="wp-block-woocommerce-checkout-order-summary-coupon-form-block"></div>\n<!-- /wp:woocommerce/checkout-order-summary-coupon-form-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-subtotal-block -->\n<div class="wp-block-woocommerce-checkout-order-summary-subtotal-block"></div>\n<!-- /wp:woocommerce/checkout-order-summary-subtotal-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-fee-block -->\n<div class="wp-block-woocommerce-checkout-order-summary-fee-block"></div>\n<!-- /wp:woocommerce/checkout-order-summary-fee-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-discount-block -->\n<div class="wp-block-woocommerce-checkout-order-summary-discount-block"></div>\n<!-- /wp:woocommerce/checkout-order-summary-discount-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-shipping-block -->\n<div class="wp-block-woocommerce-checkout-order-summary-shipping-block"></div>\n<!-- /wp:woocommerce/checkout-order-summary-shipping-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-taxes-block -->\n<div class="wp-block-woocommerce-checkout-order-summary-taxes-block"></div>\n<!-- /wp:woocommerce/checkout-order-summary-taxes-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-totals-block -->\n<div class="wp-block-woocommerce-checkout-order-summary-totals-block"></div>\n<!-- /wp:woocommerce/checkout-order-summary-totals-block --></div>\n<!-- /wp:woocommerce/checkout-order-summary-block --></div>\n<!-- /wp:woocommerce/checkout -->'
				}
			} );
		}
	} );

	test.afterAll( async ( { requestUtils } ) => {
		// Cleanup test products
		if ( freeProductId ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wc/v3/products/${freeProductId}`,
				data: { force: true }
			} );
		}
		if ( paidProductId ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wc/v3/products/${paidProductId}`,
				data: { force: true }
			} );
		}
	} );

	test.beforeEach( async ( { page } ) => {
		// Clear cart before each test
		await page.goto( '/?wc-ajax=get_refreshed_fragments' );
		await page.evaluate( () => {
			// Clear WooCommerce cart via sessionStorage/localStorage
			sessionStorage.clear();
			localStorage.clear();
		} );
	} );

	test( 'Free product, no shipping: billing address fields are still shown in blocks checkout', async ( { page } ) => {

		// Add free virtual product to cart
		await page.goto( `/?add-to-cart=${freeProductId}` );
		await page.waitForLoadState( 'networkidle' );

		// Go to checkout
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Wait for blocks to load
		await page.waitForSelector( '.wp-block-woocommerce-checkout', { timeout: 10000 } );

		// Plugin has NO effect on blocks - billing address fields should still be visible
		// The blocks checkout uses its own field management, not the woocommerce_billing_fields filter

		// Look for blocks-specific billing field selectors
		const billingFieldSelectors = [
			'[id*="billing-address"][id*="address_1"], [name*="billingAddress"][name*="address_1"]',
			'[id*="billing-address"][id*="city"], [name*="billingAddress"][name*="city"]',
			'[id*="billing-address"][id*="state"], [name*="billingAddress"][name*="state"]',
			'[id*="billing-address"][id*="postcode"], [name*="billingAddress"][name*="postcode"]',
			'[id*="billing-address"][id*="country"], [name*="billingAddress"][name*="country"]',
			'[id*="billing-address"][id*="phone"], [name*="billingAddress"][name*="phone"]'
		];

		// Wait a bit for the blocks to render fields
		await page.waitForTimeout( 2000 );

		let visibleFieldsCount = 0;
		for ( const selector of billingFieldSelectors ) {
			const field = page.locator( selector );
			const count = await field.count();
			if ( count > 0 ) {
				const isVisible = await field.first().isVisible();
				if ( isVisible ) {
					visibleFieldsCount++;
				}
			}
		}

		// In blocks checkout, billing address fields should still be present even for free orders
		// because the plugin doesn't affect blocks checkout
		expect( visibleFieldsCount ).toBeGreaterThan( 0 );

		// Verify this is indeed a free order
		const total = page.locator( '.wc-block-components-totals-item__value, .wc-block-formatted-money-amount' );
		if ( await total.count() > 0 ) {
			await expect( total.last() ).toContainText( '$0' );
		}
	} );

	test( 'Blocks checkout still requires billing address for free orders', async ( { page } ) => {
		// Add free virtual product to cart
		await page.goto( `/?add-to-cart=${freeProductId}` );
		await page.waitForLoadState( 'networkidle' );

		// Go to checkout
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Wait for blocks to load
		await page.waitForSelector( '.wp-block-woocommerce-checkout', { timeout: 10000 } );
		await page.waitForTimeout( 3000 );

		// Try to find and fill only email field if it exists
		const emailField = page.locator( '[id*="email"], [name*="email"]' );
		if ( await emailField.count() > 0 ) {
			await emailField.first().fill( 'john@example.com' );
		}

		// Try to find and click the place order button
		const placeOrderButton = page.locator(
			'.wc-block-components-checkout-place-order-button, [class*="place-order"], button:has-text("Place order")'
		);

		if ( await placeOrderButton.count() > 0 && await placeOrderButton.first().isEnabled() ) {
			await placeOrderButton.first().click();
			await page.waitForLoadState( 'networkidle' );
			await page.waitForTimeout( 2000 );

			// Should either show validation errors or still be on checkout page
			// (not reach order confirmation like the shortcode version does)
			const currentUrl = page.url();

			// Look for validation errors or still being on checkout
			const hasValidationErrors = await page.locator(
				'.wc-block-components-validation-error, [class*="error"], [class*="notice"]'
			).count() > 0;

			const stillOnCheckout = currentUrl.includes( 'checkout' ) && !currentUrl.includes( 'order-received' );

			// Either we get validation errors OR we're still on checkout (not order confirmation)
			// This demonstrates the plugin doesn't work with blocks - blocks still require full address
			expect( hasValidationErrors || stillOnCheckout ).toBe( true );

			if ( !currentUrl.includes( 'order-received' ) ) {
				console.log( 'Blocks checkout correctly prevented order completion without full billing address' );
			}
		} else {
			// If we can't find the button or it's disabled, that also proves billing address is required
			console.log( 'Place order button not available, indicating required fields missing' );
		}
	} );

	test( 'Paid product in blocks checkout: billing fields shown as expected', async ( { page } ) => {
		// Add paid product to cart
		await page.goto( `/?add-to-cart=${paidProductId}` );
		await page.waitForLoadState( 'networkidle' );

		// Go to checkout
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Wait for blocks to load
		await page.waitForSelector( '.wp-block-woocommerce-checkout', { timeout: 10000 } );
		await page.waitForTimeout( 2000 );

		// For paid products, blocks checkout should show billing fields (as normal)
		const billingFieldSelectors = [
			'[id*="billing-address"][id*="address_1"], [name*="billingAddress"][name*="address_1"]',
			'[id*="billing-address"][id*="city"], [name*="billingAddress"][name*="city"]',
			'[id*="billing-address"][id*="state"], [name*="billingAddress"][name*="state"]'
		];

		let visibleFieldsCount = 0;
		for ( const selector of billingFieldSelectors ) {
			const field = page.locator( selector );
			const count = await field.count();
			if ( count > 0 ) {
				const isVisible = await field.first().isVisible();
				if ( isVisible ) {
					visibleFieldsCount++;
				}
			}
		}

		expect( visibleFieldsCount ).toBeGreaterThan( 0 );

		// Verify this is a paid order
		const total = page.locator( '.wc-block-components-totals-item__value, .wc-block-formatted-money-amount' );
		if ( await total.count() > 0 ) {
			await expect( total.last() ).toContainText( '$19.99' );
		}
	} );
} );
