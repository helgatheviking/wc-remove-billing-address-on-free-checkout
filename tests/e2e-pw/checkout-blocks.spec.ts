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
import { test, expect, Page, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import {useBlocksCheckout } from "./helpers/checkout";
import {updateSetting} from "./helpers/general/rest/settings";

var isUsingBlocksCheckout = false;

/** Billing address fields that the plugin removes (the "address" fields). */
const SHORTCODE_BILLING_ADDRESS_SELECTORS = [
	'#billing_address_1',
	'#billing_city',
	'#billing_state',
	'#billing_postcode',
	'#billing_phone',
] as const;
const BLOCKS_BILLING_ADDRESS_SELECTORS = [
	'#billing-address_1',
	'#billing-city',
	'#billing-state',
	'#billing-postcode',
	'#billing-phone',
] as const;

/** Billing identity fields that should always remain. */
const SHORTCODE_BILLING_IDENTITY_SELECTORS = [
	'#billing_first_name',
	'#billing_last_name',
	'#billing_email',
] as const;
const BLOCKS_BILLING_IDENTITY_SELECTORS = [
	'#billing-first_name',
	'#billing-last_name',
	'#email',
] as const;

/** Shipping identity fields that should always remain. */
const SHORTCODE_SHIPPING_IDENTITY_SELECTORS = [
	'#shipping_first_name',
	'#shipping_last_name',
	// '#billing_email',
] as const;

const BLOCKS_SHIPPING_IDENTITY_SELECTORS = [
	'#shipping-first_name',
	'#shipping-last_name',
	'#email',
] as const;

const SHORTCODE_SHIPPING_ADDRESS_SELECTORS = [
	'#shipping_address_1',
	'#shipping_city',
	'#shipping_postcode',
] as const;
const BLOCKS_SHIPPING_ADDRESS_SELECTORS = [
	'#shipping-address_1',
	'#shipping-city',
	'#shipping-postcode',
] as const;


const ADDRESS_SELECTORS_AND_VALUES = {
	'#billing_first_name': 'Brian',
	'#billing_last_name': 'Henry',
	'#billing_email': 'brianhenryie@example.com',
	'#billing_address_1': '123 Fake Street',
	'#billing_city': 'Beverley Hills',
	// '#billing_state': 'California', // Omit because it's not a smile text field to fill.
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

const BLOCK_ADDRESS_SELECTORS_AND_VALUES = {
	'#billing-first_name': 'Brian',
	'#billing-last_name': 'Henry',
	'#email': 'brianhenryie@example.com',
	'#billing-address_1': '123 Fake Street',
	'#billing-city': 'Beverley Hills',
	'#billing-postcode': '90210',
	'#billing-phone': '1234567890',
	'#shipping-first_name': 'Brian',
	'#shipping-last_name': 'Henry',
	'#shipping-email': 'brianhenryie@example.com',
	'#shipping-address_1': '123 Fake Street',
	'#shipping-city': 'Beverley Hills',
	'#shipping-postcode': '90210',
	'#shipping-phone': '1234567890',
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

	const radio = isUsingBlocksCheckout
		? page.locator( `#radio-control-wc-payment-method-options-${ gatewayId }` )
		: page.locator( `input[name="payment_method"][value="${ gatewayId }"]` );

	// When cart is free, WooCommerce may hide payment methods entirely.
	const isVisible = await radio.isVisible().catch( () => false );
	if ( ! isVisible ) {
		return;
	}

	// If already selected, nothing to do.
	if ( await radio.isChecked() ) {
		return;
	}

	if(isUsingBlocksCheckout) {
		await radio.check();
	}else{
		// Set up a listener for the AJAX update_order_review response BEFORE clicking.
		const responsePromise = page.waitForResponse(
			(response) =>
				response.url().includes('wc-ajax=update_order_review') &&
				response.status() === 200,
			{timeout: 10000}
		);

		await radio.check();

		// Wait for the AJAX checkout update to complete (billing fields re-render).
		await responsePromise;
	}

	// Brief wait for DOM to stabilize after fragment replacement.
	await page.waitForTimeout( 500 );

	// TODO: Add a failing assert here.
}

// Use same address for billing
async function checkUseSameAddressForBilling( page: Page, setChecked: boolean ) {
	const checkbox = await page.locator( `#checkbox-control-0` );
	// await page.locator('.wc-block-checkout__use-address-for-billing').first()

	// If already checked(setChecked), nothing to do.
	const isAlreadyChecked = await checkbox.isChecked();
	if ( (setChecked && isAlreadyChecked) || (!setChecked && !isAlreadyChecked )) {
		return;
	}

	if(setChecked){
		await checkbox.check();
	}else {
		await checkbox.uncheck()
	}

	// Brief wait for DOM to stabilize after fragment replacement.
	await page.waitForTimeout( 500 );
}

/**
 * Assert that billing address fields are REMOVED (hidden).
 */
async function expectBillingAddressRemoved( page: Page ) {
	const BILLING_ADDRESS_SELECTORS = isUsingBlocksCheckout ? BLOCKS_BILLING_ADDRESS_SELECTORS : SHORTCODE_BILLING_ADDRESS_SELECTORS;
	for ( const selector of BILLING_ADDRESS_SELECTORS ) {
		await expect( page.locator( selector ) ).not.toBeVisible();
	}
}

/**
 * Assert that billing address fields are SHOWN (visible).
 */
async function expectBillingAddressShown( page: Page ) {
	const BILLING_ADDRESS_SELECTORS = isUsingBlocksCheckout ? BLOCKS_BILLING_ADDRESS_SELECTORS : SHORTCODE_BILLING_ADDRESS_SELECTORS;
	for ( const selector of BILLING_ADDRESS_SELECTORS ) {
		await expect( page.locator( selector ) ).toBeVisible();
	}
}

/**
 * Assert that billing identity fields (name, email) are always visible.
 */
async function expectShippingIdentityShown( page: Page ) {
	const SHIPPING_IDENTITY_SELECTORS = isUsingBlocksCheckout ? BLOCKS_SHIPPING_IDENTITY_SELECTORS : SHORTCODE_SHIPPING_IDENTITY_SELECTORS;
	for ( const selector of SHIPPING_IDENTITY_SELECTORS ) {
		await expect( page.locator( selector ) ).toBeVisible();
	}
}

/**
 * Assert that billing identity fields (name, email) are always visible.
 */
async function expectBillingIdentityShown( page: Page ) {
	const BILLING_IDENTITY_SELECTORS = isUsingBlocksCheckout ? BLOCKS_BILLING_IDENTITY_SELECTORS : SHORTCODE_BILLING_IDENTITY_SELECTORS;
	for ( const selector of BILLING_IDENTITY_SELECTORS ) {
		await expect( page.locator( selector ) ).toBeVisible();
	}
}

/**
 * Assert shipping fields are visible.
 */
async function expectShippingShown( page: Page ) {
	const SHIPPING_ADDRESS_SELECTORS = isUsingBlocksCheckout ? BLOCKS_SHIPPING_ADDRESS_SELECTORS : SHORTCODE_SHIPPING_ADDRESS_SELECTORS;

	for ( const selector of SHIPPING_ADDRESS_SELECTORS ) {
		await expect( page.locator( selector ) ).toBeVisible();
	}
}

/**
 * Assert shipping fields are hidden.
 *
 * In blocks checkout, virtual products don't render shipping fields at all.
 * Use hyphenated IDs (#shipping-*) for blocks, underscored (#shipping_*) for shortcode.
 */
async function expectShippingHidden( page: Page ) {
	if ( isUsingBlocksCheckout ) {
		await expect( page.locator( '#shipping-first_name' ) ).not.toBeVisible();
		await expect( page.locator( '#shipping-address_1' ) ).not.toBeVisible();
		await expect( page.locator( '#shipping-city' ) ).not.toBeVisible();
		await expect( page.locator( '#shipping-postcode' ) ).not.toBeVisible();
	} else {
		await expect( page.locator( '#shipping_first_name' ) ).not.toBeVisible();
		await expect( page.locator( '#shipping_last_name' ) ).not.toBeVisible();
		await expect( page.locator( '#shipping_address_1' ) ).not.toBeVisible();
		await expect( page.locator( '#shipping_city' ) ).not.toBeVisible();
		await expect( page.locator( '#shipping_postcode' ) ).not.toBeVisible();
	}
}

/**
 * Click place order and confirm success on order confirmation page.
 */
async function placeOrder( page: Page ) {
	const ADDRESS_SELECTORS = Object.keys(BLOCK_ADDRESS_SELECTORS_AND_VALUES)

	for(let i = 0; i < ADDRESS_SELECTORS.length; i++){
		const selector = ADDRESS_SELECTORS[i];
		const value = BLOCK_ADDRESS_SELECTORS_AND_VALUES[selector];
		if ( await page.isVisible( selector ) ) {
			await page.fill( selector, value );
		}
	}

	// await page.waitForLoadState( 'networkidle' );
	// await page.waitForTimeout( 2500 );

	if(isUsingBlocksCheckout){
		const placeOrderButton = await page.locator( '.wc-block-checkout__actions_row' ).first().getByText( 'Place Order' );
		await placeOrderButton.click();
	} else {
		await page.click( '#place_order' );
	}

	// Wait for order-received page.
	await page.waitForURL( /order-received/, { timeout: 30000 } );

	// Verify order received.
	await expect(
		page.locator( '.woocommerce-thankyou-order-received, .woocommerce-order-received' ).first()
	).toBeVisible();
}

const SHIPPING_DESTINATION_OPTIONS = {
	SHIPPING: 'shipping', // "Default to customer shipping address". – shows the shipping address form
	BILLING: 'billing', // "Default to customer billing address" – hides the shipping address form until "Ship to another address" is checked
	BILLING_ONLY: 'billing_only', // "Force shipping to the customer billing addres" – only displays the billing form, ships to that address.
} as const;

async function updateShippingDestinationSetting(requestUtils: RequestUtils,  value: string ): Promise<void> {
	updateSetting(requestUtils,'woocommerce_ship_to_destination', value);
}

test.describe( 'Blocks Checkout – Billing/Shipping Field Visibility', () => {

	test.beforeAll( async ( { requestUtils } ) => {

		// TODO: run all tests with all three options (already done for shortcode).
		await updateShippingDestinationSetting( requestUtils, SHIPPING_DESTINATION_OPTIONS.BILLING )

		await useBlocksCheckout(requestUtils);
		isUsingBlocksCheckout = true;

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

	test( 'Blocks: Free Virtual + Gateway 1 (requires billing): billing address removed, shipping hidden', async ( {
																											   page,
																										   } ) => {
		await addToCartAndCheckout( page, productIds.freeVirtual );
		await selectGateway( page, 'test_gateway_one' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressRemoved( page );
		await expectShippingHidden( page );

		await placeOrder( page );
	} );

	test( 'Blocks: Free Virtual + Gateway 2 (no billing addr req): billing address removed, shipping hidden', async ( {
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

	test( 'Blocks: Paid Virtual + Gateway 1 (requires billing): billing address shown, shipping hidden', async ( {
																											 page,
																										 } ) => {
		await addToCartAndCheckout( page, productIds.paidVirtual );
		await selectGateway( page, 'test_gateway_one' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressShown( page );
		await expectShippingHidden( page );

		await placeOrder( page );
	} );

	test( 'Blocks: Paid Virtual + Gateway 2 (no billing addr req): billing address removed, shipping hidden', async ( {
																											 page,
																										 } ) => {
		await addToCartAndCheckout( page, productIds.paidVirtual );
		await selectGateway( page, 'test_gateway_two' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressRemoved( page );
		await expectShippingHidden( page );

		// await page.locator('.wc-block-components-address-form__postcode', { has: page.locator('#billing-city') }).first().set

		// Set one as visible immediately before clicking placeOrder in case it highlights red for failed validation.
		// await page.evaluate(() => document.querySelector('.wc-block-components-address-form__postcode #billing-city').style.display = 'block');

		// const billingCity = await page.locator('.wc-block-components-address-form__postcode', { has: page.locator('#billing-city') }).first();
		// await billingCity.evaluate(element => element.style.display = 'block');
		// await billingCity.evaluate("element => element.style.setProperty('display', 'block' , 'important')");

		// div.evaluate("element => element.style.setProperty('display', 'block' , 'important')")

		await placeOrder( page );
	} );

	// ──────────────────────────────────────────────────
	// FREE SHIPPED PRODUCT
	// Cart total = $0, shipping IS needed
	// ──────────────────────────────────────────────────

	test( 'Blocks: Free Shipped + Gateway 1 (requires shipping): billing address hidden, shipping shown', async ( {
																											page,
																										} ) => {
		await addToCartAndCheckout( page, productIds.freeShipped );
		await selectGateway( page, 'test_gateway_one' );

		await expectBillingAddressRemoved( page );
		await expectShippingIdentityShown( page );
		await expectShippingShown( page );

		await placeOrder( page );
	} );

	//
	test( 'Blocks: Free Shipped + Gateway 2 (no billing addr req): billing address not shown, shipping shown', async ( {
																											  page,
																										  } ) => {
		await addToCartAndCheckout( page, productIds.freeShipped );

		// $0 cart hides all payment gateways — can't select gw2.

		await expectShippingIdentityShown( page );
		await expectBillingAddressRemoved( page );
		await expectShippingShown( page );

		await placeOrder( page );
	} );

	// ──────────────────────────────────────────────────
	// PAID SHIPPED PRODUCT
	// Cart total > $0, shipping IS needed
	// ──────────────────────────────────────────────────

	test( 'Blocks: Paid Shipped + Gateway 1 (requires billing): billing address shown, shipping shown', async ( {
																											page,
																										} ) => {
		await addToCartAndCheckout( page, productIds.paidShipped );

		await checkUseSameAddressForBilling(page, false);

		await selectGateway( page, 'test_gateway_one' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressShown( page );
		await expectShippingIdentityShown( page );
		await expectShippingShown( page );

		await placeOrder( page );
	} );

	test( 'Blocks: Paid Shipped + Gateway 2 (no billing addr req): billing address removed, shipping shown', async ( {
																											page,
																										} ) => {
		await addToCartAndCheckout( page, productIds.paidShipped );

		// Use same address for billing
		// I thought this would be controlled by WooCommerce settings that is set in beforeAll.
		await checkUseSameAddressForBilling(page, false);

		await selectGateway( page, 'test_gateway_two' );

		await expectBillingIdentityShown( page );
		await expectBillingAddressRemoved( page );
		await expectShippingShown( page );

		await selectGateway( page, 'test_gateway_one' );

		await expectBillingAddressShown( page );

		await selectGateway( page, 'test_gateway_two' );

		await placeOrder( page );
	} );

	// ──────────────────────────────────────────────────────────────────
	// DATA PRESERVATION
	// Hiding billing fields at checkout must NOT delete saved customer
	// address data from the database.
	// ──────────────────────────────────────────────────────────────────

	test( 'Blocks: Paid Virtual + Gateway 2: saved customer billing address is preserved in the database after checkout', async ( {
																															  page,
																															  requestUtils,
																														  } ) => {
		const timestamp = Date.now();
		const customerData = {
			username: 'address_test_' + timestamp,
			email: `address_test_${ timestamp }@example.com`,
			password: 'password',
			first_name: 'Jane',
			last_name: 'Doe',
			billing: {
				first_name: 'Jane',
				last_name: 'Doe',
				address_1: '742 Evergreen Terrace',
				city: 'Springfield',
				state: 'IL',
				postcode: '62704',
				country: 'US',
				email: `address_test_${ timestamp }@example.com`,
				phone: '5551234567',
			},
		};

		// 1. Create customer via WP REST.
		const user = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/users',
			data: {
				username: customerData.username,
				email: customerData.email,
				password: customerData.password,
				first_name: customerData.first_name,
				last_name: customerData.last_name,
				roles: [ 'customer' ],
			},
		} );
		const userId = user.id;

		// 2. Set the WooCommerce customer address
		const response = await requestUtils.rest({
			method: 'PUT',
			path: `/wc/v3/customers/${ userId }`,
			data: {"billing": customerData.billing, "shipping":customerData.billing}
		}); expect( response.ok );

		// 3. Log in as the customer.
		await page.context().clearCookies();
		await page.goto( '/my-account/' );
		await page.waitForLoadState( 'networkidle' );
		await page.fill( '#username', customerData.username );
		await page.fill( '#password', customerData.password );
		await page.click( 'button[name="login"]' );
		await page.waitForLoadState( 'networkidle' );
		await expect( page.locator( '.woocommerce-MyAccount-content' ) ).toBeVisible( { timeout: 10000 } );

		// 4. Now place an order with gw2 (billing hidden).
		await page.goto( `/?add-to-cart=${ productIds.paidVirtual }` );
		await page.waitForLoadState( 'networkidle' );
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Checkout fields are hidden behind an "Edit" button
		const editButton = await page.locator('.wc-block-components-address-card__edit');
		if ( await editButton.first().isVisible() ) {
			await editButton.first().click();
			await page.waitForLoadState( 'networkidle' );
			await page.waitForTimeout( 500 );
		}

		await selectGateway( page, 'test_gateway_two' );

		// Billing address fields should be hidden.
		await expectBillingAddressRemoved( page );

		await page.fill('#billing-first_name', customerData.billing.first_name);
		await page.fill( '#billing-last_name', customerData.billing.last_name );
		await page.fill('#email', customerData.billing.email);

		if(isUsingBlocksCheckout){
			const placeOrderButton = await page.locator( '.wc-block-checkout__actions_row' ).first().getByText( 'Place Order' );
			await placeOrderButton.click();
		} else {
			await page.click( '#place_order' );
		}

		await page.waitForURL( /order-received/, { timeout: 30000 } );
		await expect(
			page.locator( '.woocommerce-thankyou-order-received, .woocommerce-order-received' ).first()
		).toBeVisible();

		// 5. Verify the saved billing address is still intact.
		//    Navigate to My Account → Edit Address to check stored values.
		await page.goto( '/my-account/edit-address/billing/' );
		await page.waitForLoadState( 'networkidle' );

		await expect( page.locator( '#billing_address_1' ) ).toHaveValue( '742 Evergreen Terrace' );
		await expect( page.locator( '#billing_city' ) ).toHaveValue( 'Springfield' );
		await expect( page.locator( '#billing_postcode' ) ).toHaveValue( '62704' );
		await expect( page.locator( '#billing_phone' ) ).toHaveValue( '5551234567' );

		// 6. Clean up: delete the test customer.
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/users/${ userId }?force=true&reassign=1`,
		} );
	} );
} );
