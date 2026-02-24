/**
 * Test the plugin's behavior on the classic [woocommerce_checkout] shortcode.
 * The plugin hooks into 'woocommerce_billing_fields' which is used by shortcode checkout.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { loginAsAdmin } from './helpers/ui/login';

test.describe( 'WC Remove Billing Address - Shortcode Checkout', () => {
	let freeProductId: string;
	let paidProductId: string;
	let freeShippingProductId: string;
	let checkoutPageId: string;

	test.beforeAll( async ( { requestUtils } ) => {
		// Create test products using REST API
		const freeProduct = await requestUtils.rest( {
			method: 'POST',
			path: '/wc/v3/products',
			data: {
				name: 'Free Virtual Product E2E',
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
				name: 'Paid Simple Product E2E',
				type: 'simple',
				regular_price: '19.99',
				status: 'publish'
			}
		} );
		paidProductId = paidProduct.id;

		const freeShippingProduct = await requestUtils.rest( {
			method: 'POST',
			path: '/wc/v3/products',
			data: {
				name: 'Free Product with Shipping E2E',
				type: 'simple',
				regular_price: '0.00',
				virtual: false,
				weight: '1',
				status: 'publish'
			}
		} );
		freeShippingProductId = freeShippingProduct.id;

		// Get the checkout page and ensure it uses shortcode
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
			// Update checkout page to use shortcode
			await requestUtils.rest( {
				method: 'POST',
				path: `/wp/v2/pages/${checkoutPageId}`,
				data: {
					content: '[woocommerce_checkout]'
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
		if ( freeShippingProductId ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wc/v3/products/${freeShippingProductId}`,
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

	test( 'Free product, no shipping: billing address fields are removed', async ( { page } ) => {
		// Add free virtual product to cart
		await page.goto( `/?add-to-cart=${freeProductId}` );
		await page.waitForLoadState( 'networkidle' );

		// Go to checkout
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Verify billing address fields are NOT visible
		await expect( page.locator( '#billing_address_1' ) ).not.toBeVisible();
		await expect( page.locator( '#billing_city' ) ).not.toBeVisible();
		await expect( page.locator( '#billing_state' ) ).not.toBeVisible();
		await expect( page.locator( '#billing_postcode' ) ).not.toBeVisible();
		await expect( page.locator( '#billing_country' ) ).not.toBeVisible();
		await expect( page.locator( '#billing_phone' ) ).not.toBeVisible();

		// Verify name and email fields ARE still visible
		await expect( page.locator( '#billing_first_name' ) ).toBeVisible();
		await expect( page.locator( '#billing_last_name' ) ).toBeVisible();
		await expect( page.locator( '#billing_email' ) ).toBeVisible();

		// Verify cart total is $0.00
		await expect( page.locator( '.order-total .woocommerce-Price-amount' ) ).toContainText( '$0.00' );
	} );

	test( 'Free product, no shipping: can complete checkout with only name+email', async ( { page } ) => {
		// Add free virtual product to cart
		await page.goto( `/?add-to-cart=${freeProductId}` );
		await page.waitForLoadState( 'networkidle' );

		// Go to checkout
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Fill only required fields that should be visible
		await page.fill( '#billing_first_name', 'John' );
		await page.fill( '#billing_last_name', 'Doe' );
		await page.fill( '#billing_email', 'john@example.com' );

		// Place the order
		await page.click( '#place_order' );

		// Wait for order confirmation or error
		await page.waitForLoadState( 'networkidle' );

		// Should succeed and reach order received page
		await expect( page ).toHaveURL( /order-received/ );
		await expect( page.locator( '.entry-title' ) ).toContainText( 'Order received' );
	} );

	test( 'Paid product: all billing fields are shown', async ( { page } ) => {
		// Add paid product to cart
		await page.goto( `/?add-to-cart=${paidProductId}` );
		await page.waitForLoadState( 'networkidle' );

		// Go to checkout
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Verify all standard billing fields ARE visible
		await expect( page.locator( '#billing_first_name' ) ).toBeVisible();
		await expect( page.locator( '#billing_last_name' ) ).toBeVisible();
		await expect( page.locator( '#billing_email' ) ).toBeVisible();
		await expect( page.locator( '#billing_address_1' ) ).toBeVisible();
		await expect( page.locator( '#billing_city' ) ).toBeVisible();
		await expect( page.locator( '#billing_state' ) ).toBeVisible();
		await expect( page.locator( '#billing_postcode' ) ).toBeVisible();
		await expect( page.locator( '#billing_country' ) ).toBeVisible();
		await expect( page.locator( '#billing_phone' ) ).toBeVisible();

		// Verify cart total is > $0.00
		await expect( page.locator( '.order-total .woocommerce-Price-amount' ) ).toContainText( '$19.99' );
	} );

	test( 'Mixed cart (free + paid): all billing fields shown', async ( { page } ) => {
		// Add both free and paid products to cart
		await page.goto( `/?add-to-cart=${freeProductId}` );
		await page.waitForLoadState( 'networkidle' );
		await page.goto( `/?add-to-cart=${paidProductId}` );
		await page.waitForLoadState( 'networkidle' );

		// Go to checkout
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Verify all billing fields are shown (cart total > 0)
		await expect( page.locator( '#billing_first_name' ) ).toBeVisible();
		await expect( page.locator( '#billing_last_name' ) ).toBeVisible();
		await expect( page.locator( '#billing_email' ) ).toBeVisible();
		await expect( page.locator( '#billing_address_1' ) ).toBeVisible();
		await expect( page.locator( '#billing_city' ) ).toBeVisible();
		await expect( page.locator( '#billing_state' ) ).toBeVisible();
		await expect( page.locator( '#billing_postcode' ) ).toBeVisible();
		await expect( page.locator( '#billing_country' ) ).toBeVisible();
		await expect( page.locator( '#billing_phone' ) ).toBeVisible();

		// Verify cart total is $19.99 (free + paid)
		await expect( page.locator( '.order-total .woocommerce-Price-amount' ) ).toContainText( '$19.99' );
	} );

	test( 'Free product with shipping: billing fields shown', async ( { page } ) => {
		// Add free product that requires shipping
		await page.goto( `/?add-to-cart=${freeShippingProductId}` );
		await page.waitForLoadState( 'networkidle' );

		// Go to checkout
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Even though product is free, shipping is needed so billing fields should be shown
		await expect( page.locator( '#billing_first_name' ) ).toBeVisible();
		await expect( page.locator( '#billing_last_name' ) ).toBeVisible();
		await expect( page.locator( '#billing_email' ) ).toBeVisible();
		await expect( page.locator( '#billing_address_1' ) ).toBeVisible();
		await expect( page.locator( '#billing_city' ) ).toBeVisible();
		await expect( page.locator( '#billing_state' ) ).toBeVisible();
		await expect( page.locator( '#billing_postcode' ) ).toBeVisible();
		await expect( page.locator( '#billing_country' ) ).toBeVisible();
		await expect( page.locator( '#billing_phone' ) ).toBeVisible();

		// Shipping form should also be present
		await expect( page.locator( '#shipping_first_name, [name="shipping_first_name"]' ) ).toBeVisible();
	} );
} );