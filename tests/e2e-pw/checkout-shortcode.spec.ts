/**
 * Test billing/shipping field visibility on the classic [woocommerce_checkout] shortcode.
 *
 * ## Variables
 *
 * 1. Product type (4): Free Virtual, Paid Virtual, Free Shipped, Paid Shipped
 * 2. Gateway (2): test_gateway_one (requires billing), test_gateway_two (require_billing_fields=false)
 *
 * ## Expected Outcomes (8 combinations)
 *
 * | Product        | Gateway (billing req?) | Billing Address | Shipping | Why                                        |
 * |----------------|------------------------|-----------------|----------|--------------------------------------------|
 * | Free Virtual   | gw1 (yes)              | REMOVED         | Hidden   | Free + no shipping → default removal       |
 * | Free Virtual   | gw2 (no)               | REMOVED         | Hidden   | Free + no shipping; also gw doesn't need   |
 * | Paid Virtual   | gw1 (yes)              | SHOWN           | Hidden   | Not free, gateway requires billing          |
 * | Paid Virtual   | gw2 (no)               | REMOVED         | Hidden   | require_billing_fields=false                |
 * | Free Shipped   | gw1 (yes)              | SHOWN           | Shown    | Free but needs shipping → no free removal   |
 * | Free Shipped   | gw2 (no)               | SHOWN           | Shown    | $0 cart hides gateways → can't select gw2   |
 * | Paid Shipped   | gw1 (yes)              | SHOWN           | Shown    | Not free, needs shipping, gw requires       |
 * | Paid Shipped   | gw2 (no)               | REMOVED         | Shown    | require_billing_fields=false                |
 *
 * Products are created in {@see initialize-internal.sh}.
 * Gateways are registered in {@see development-plugin.php}.
 */
import { test, expect, Page } from '@wordpress/e2e-test-utils-playwright';

const SHORTCODE_CHECKOUT_CONTENT =
	'<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->';

/** Billing address fields that the plugin removes (the "address" fields). */
const BILLING_ADDRESS_SELECTORS = [
	'#billing_address_1',
	'#billing_city',
	'#billing_postcode',
	'#billing_phone',
] as const;

/** Billing identity fields that should always remain. */
const BILLING_IDENTITY_SELECTORS = [
	'#billing_first_name',
	'#billing_last_name',
	'#billing_email',
] as const;


const ADDRESS_SELECTORS_AND_VALUES = {
	'#billing_first_name': 'Brian',
	'#billing_last_name': 'Henry',
	'#billing_email': 'brianhenryie@example.com',
	'#billing_address_1': '123 Fake Street',
	'#billing_city': 'Beverley Hills',
	'#billing_postcode': '90210',
	'#billing_phone': '1234567890',
	'#shipping_first_name': 'Brian',
	'#shipping_last_name': 'Henry',
	'#shipping_email': 'brianhenryie@example.com',
	'#shipping_address_1': '123 Fake Street',
	'#shipping_city': 'Beverley Hills',
	'#shipping_postcode': '90210',
	'#shipping_phone': '1234567890',
} as const;

/** Product slugs from initialize-internal.sh */
const PRODUCTS = {
	freeVirtual: 'Free Digital Product',
	paidVirtual: 'Digital Product',
	freeShipped: 'Free Shipped Product',
	paidShipped: 'Shipped Product',
} as const;

/** Maps product name → ID, populated in beforeAll. */
const productIds: Record< string, number > = {};

/**
 * Empty the cart and add a single product, then navigate to checkout.
 *
 * Clears cookies to reset the WooCommerce session (empty cart), then
 * adds the product via the `?add-to-cart=` URL and navigates to checkout.
 * The test runs as a guest (not logged in) to avoid pre-filled address data.
 */
async function addToCartAndCheckout( page: Page, productId: number ) {
	// Clear all cookies to reset WC session / empty cart.
	await page.context().clearCookies();

	// Add product to cart (as guest).
	await page.goto( `/?add-to-cart=${ productId }` );
	await page.waitForLoadState( 'networkidle' );

	// Navigate to checkout.
	await page.goto( '/checkout/' );
	await page.waitForLoadState( 'networkidle' );
}

/**
 * Select a payment gateway by clicking its radio button and wait for the
 * checkout to update (billing fields may re-render via AJAX fragments).
 *
 * When cart total is $0, WooCommerce hides payment methods entirely —
 * in that case this is a no-op.
 */
async function selectGateway( page: Page, gatewayId: string ) {
	const radio = page.locator( `input[name="payment_method"][value="${ gatewayId }"]` );

	// When cart is free, WooCommerce may hide payment methods entirely.
	const isVisible = await radio.isVisible().catch( () => false );
	if ( ! isVisible ) {
		return;
	}

	// If already selected, nothing to do.
	if ( await radio.isChecked() ) {
		return;
	}

	// Set up a listener for the AJAX update_order_review response BEFORE clicking.
	const responsePromise = page.waitForResponse(
		( response ) =>
			response.url().includes( 'wc-ajax=update_order_review' ) &&
			response.status() === 200,
		{ timeout: 10000 }
	);

	await radio.check();

	// Wait for the AJAX checkout update to complete (billing fields re-render).
	await responsePromise;

	// Brief wait for DOM to stabilize after fragment replacement.
	await page.waitForTimeout( 500 );
}

/**
 * Assert that billing address fields are REMOVED (hidden).
 */
async function expectBillingAddressRemoved( page: Page ) {
	for ( const selector of BILLING_ADDRESS_SELECTORS ) {
		await expect( page.locator( selector ) ).not.toBeVisible();
	}
}

/**
 * Assert that billing address fields are SHOWN (visible).
 */
async function expectBillingAddressShown( page: Page ) {
	for ( const selector of BILLING_ADDRESS_SELECTORS ) {
		await expect( page.locator( selector ) ).toBeVisible();
	}
}

/**
 * Assert that billing identity fields (name, email) are always visible.
 */
async function expectBillingIdentityShown( page: Page ) {
	for ( const selector of BILLING_IDENTITY_SELECTORS ) {
		await expect( page.locator( selector ) ).toBeVisible();
	}
}

/**
 * Assert shipping fields are visible.
 */
async function expectShippingShown( page: Page ) {
	await expect(
		page.locator( '#ship-to-different-address-checkbox, #shipping_first_name' ).first()
	).toBeVisible();
}

/**
 * Assert shipping fields are hidden.
 */
async function expectShippingHidden( page: Page ) {
	await expect( page.locator( '#shipping_first_name' ) ).not.toBeVisible();
	await expect( page.locator( '#shipping_last_name' ) ).not.toBeVisible();
	await expect( page.locator( '#shipping_address_1' ) ).not.toBeVisible();
	await expect( page.locator( '#shipping_city' ) ).not.toBeVisible();
	await expect( page.locator( '#shipping_postcode' ) ).not.toBeVisible();
}

/**
 * Click place order and confirm success on order confirmation page.
 */
async function placeOrder( page: Page ) {
	const ADDRESS_SELECTORS = Object.keys(ADDRESS_SELECTORS_AND_VALUES)

	for(let i = 0; i < ADDRESS_SELECTORS.length; i++){
		const selector = ADDRESS_SELECTORS[i];
		const value = ADDRESS_SELECTORS_AND_VALUES[selector];
		if ( await page.isVisible( selector ) ) {
			await page.fill( selector, value );
		}
	}

	await page.click( '#place_order' );

	// Wait for order-received page.
	await page.waitForURL( /order-received/, { timeout: 30000 } );

	// Verify order received.
	await expect(
		page.locator( '.woocommerce-thankyou-order-received, .woocommerce-order-received' ).first()
	).toBeVisible();
}

test.describe( 'Shortcode Checkout – Billing/Shipping Field Visibility', () => {

	test.beforeAll( async ( { requestUtils } ) => {
		// Set checkout page to use shortcode.
		const pages = await requestUtils.rest( {
			method: 'GET',
			path: '/wp/v2/pages',
			data: { search: 'Checkout', per_page: 100 },
		} );
		const checkoutPage = pages.find(
			( p: any ) => p.title.rendered.toLowerCase() === 'checkout'
		);
		if ( checkoutPage ) {
			await requestUtils.rest( {
				method: 'POST',
				path: `/wp/v2/pages/${ checkoutPage.id }`,
				data: { content: SHORTCODE_CHECKOUT_CONTENT },
			} );
		}

		// Look up product IDs via WooCommerce Store API (public, no auth needed).
		for ( const [ key, name ] of Object.entries( PRODUCTS ) ) {
			const response = await requestUtils.request.get(
				'/wp-json/wc/store/v1/products',
				{ params: { search: name, per_page: '10' } }
			);
			const products = await response.json();
			const match = products.find( ( p: any ) => p.name === name );
			if ( match ) {
				productIds[ key ] = match.id;
			} else {
				throw new Error( `Product "${ name }" not found. Run wp-env start && npm run wp-env:init.` );
			}
		}
	} );

	// ──────────────────────────────────────────────────────
	// FREE VIRTUAL PRODUCT
	// Cart total = $0, no shipping needed → billing removed
	// ──────────────────────────────────────────────────────

	test( 'Free Virtual + Gateway 1 (requires billing): billing address removed, shipping hidden', async ( {
		page,
	} ) => {
		await addToCartAndCheckout( page, productIds.freeVirtual );
		await selectGateway( page, 'test_gateway_one' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressRemoved( page );
		await expectShippingHidden( page );

		await placeOrder( page );
	} );

	test( 'Free Virtual + Gateway 2 (no billing req): billing address removed, shipping hidden', async ( {
		page,
	} ) => {
		await addToCartAndCheckout( page, productIds.freeVirtual );
		await selectGateway( page, 'test_gateway_two' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressRemoved( page );
		await expectShippingHidden( page );

		await placeOrder( page );
	} );

	// ──────────────────────────────────────────────────
	// PAID VIRTUAL PRODUCT
	// Cart total > $0, no shipping needed
	// ──────────────────────────────────────────────────

	test( 'Paid Virtual + Gateway 1 (requires billing): billing address shown, shipping hidden', async ( {
		page,
	} ) => {
		await addToCartAndCheckout( page, productIds.paidVirtual );
		await selectGateway( page, 'test_gateway_one' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressShown( page );
		await expectShippingHidden( page );

		await placeOrder( page );
	} );

	test( 'Paid Virtual + Gateway 2 (no billing req): billing address removed, shipping hidden', async ( {
		page,
	} ) => {
		await addToCartAndCheckout( page, productIds.paidVirtual );
		await selectGateway( page, 'test_gateway_two' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressRemoved( page );
		await expectShippingHidden( page );

		await placeOrder( page );
	} );

	// ──────────────────────────────────────────────────
	// FREE SHIPPED PRODUCT
	// Cart total = $0, shipping IS needed
	// ──────────────────────────────────────────────────

	test( 'Free Shipped + Gateway 1 (requires billing): billing address shown, shipping shown', async ( {
		page,
	} ) => {
		await addToCartAndCheckout( page, productIds.freeShipped );
		await selectGateway( page, 'test_gateway_one' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressShown( page );
		await expectShippingShown( page );

		await placeOrder( page );
	} );

	test( 'Free Shipped + Gateway 2 (no billing req): billing address shown (no gateways for $0 cart), shipping shown', async ( {
		page,
	} ) => {
		await addToCartAndCheckout( page, productIds.freeShipped );
		// $0 cart hides all payment gateways — can't select gw2.
		await selectGateway( page, 'test_gateway_two' );

		await expectBillingIdentityShown( page );
		// Billing remains because no gateway can be selected to opt out.
		await expectBillingAddressShown( page );
		await expectShippingShown( page );

		await placeOrder( page );
	} );

	// ──────────────────────────────────────────────────
	// PAID SHIPPED PRODUCT
	// Cart total > $0, shipping IS needed
	// ──────────────────────────────────────────────────

	test( 'Paid Shipped + Gateway 1 (requires billing): billing address shown, shipping shown', async ( {
		page,
	} ) => {
		await addToCartAndCheckout( page, productIds.paidShipped );
		await selectGateway( page, 'test_gateway_one' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressShown( page );
		await expectShippingShown( page );

		await placeOrder( page );
	} );

	test( 'Paid Shipped + Gateway 2 (no billing req): billing address removed, shipping shown', async ( {
		page,
	} ) => {
		await addToCartAndCheckout( page, productIds.paidShipped );
		await selectGateway( page, 'test_gateway_two' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressRemoved( page );
		await expectShippingShown( page );

		await placeOrder( page );
	} );
} );
