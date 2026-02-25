/**
 * Test the plugin's behavior on the classic [woocommerce_checkout] shortcode.
 *
 * The plugin hooks into 'woocommerce_billing_fields' to remove address fields
 * when the cart total is $0.00 and no shipping is needed.
 *
 * Products are created by tests/_wp-env/initialize-internal.sh:
 * - "Free Digital Product" (virtual, $0.00)
 * - "Digital Product" (virtual, $9.99)
 * - "Free Shipped Product" ($0.00, needs shipping)
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'WC Remove Billing Address - Shortcode Checkout', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		// Ensure the checkout page uses shortcode.
		const pages = await requestUtils.rest( {
			method: 'GET',
			path: '/wp/v2/pages?search=Checkout&per_page=1',
		} );

		if ( pages && pages.length > 0 ) {
			await requestUtils.rest( {
				method: 'POST',
				path: `/wp/v2/pages/${ pages[ 0 ].id }`,
				data: {
					content:
						'<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
				},
			} );
		}
	} );

	/**
	 * Helper: empty the WC cart, add a product by ID, go to checkout.
	 *
	 * Uses the `?add-to-cart=ID` URL which works for all product types
	 * including free products (which show "Read more" instead of "Add to cart"
	 * on the shop page).
	 */
	async function emptyCartAddProductAndCheckout(
		page: any,
		productId: number
	): Promise< void > {
		// Empty the cart via the cart page.
		await page.goto( '/cart/' );
		while ( ( await page.locator( 'a.remove' ).count() ) > 0 ) {
			await page.locator( 'a.remove' ).first().click();
			await page.waitForLoadState( 'networkidle' );
		}

		// Add product to cart by ID.
		await page.goto( `/?add-to-cart=${ productId }` );
		await page.waitForLoadState( 'networkidle' );

		// Go to checkout.
		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );
	}

	// Product IDs from initialize-internal.sh.
	const FREE_VIRTUAL_ID = 11;
	const PAID_VIRTUAL_ID = 10;
	const FREE_SHIPPING_ID = 13;
	const PAID_PRODUCT_ID = 14;

	test( 'free virtual product: billing address fields are removed', async ( {
		page,
	} ) => {
		await emptyCartAddProductAndCheckout( page, FREE_VIRTUAL_ID );

		// Address fields should NOT be visible.
		await expect(
			page.locator( '#billing_address_1' )
		).not.toBeVisible();
		await expect( page.locator( '#billing_city' ) ).not.toBeVisible();
		await expect(
			page.locator( '#billing_postcode' )
		).not.toBeVisible();
		await expect( page.locator( '#billing_phone' ) ).not.toBeVisible();

		// Name and email should still be visible.
		await expect(
			page.locator( '#billing_first_name' )
		).toBeVisible();
		await expect(
			page.locator( '#billing_last_name' )
		).toBeVisible();
		await expect( page.locator( '#billing_email' ) ).toBeVisible();
	} );

	test( 'free virtual product: can complete checkout with only name and email', async ( {
		page,
	} ) => {
		await emptyCartAddProductAndCheckout( page, FREE_VIRTUAL_ID );

		await page.fill( '#billing_first_name', 'John' );
		await page.fill( '#billing_last_name', 'Doe' );
		await page.fill( '#billing_email', 'john@example.com' );

		await page.click( '#place_order' );
		await page.waitForURL( /order-received/, { timeout: 30000 } );

		await expect(
			page.locator( 'text=Order received' ).first()
		).toBeVisible();
	} );

	test( 'paid product: all billing fields are shown', async ( {
		page,
	} ) => {
		await emptyCartAddProductAndCheckout( page, PAID_PRODUCT_ID );

		await expect(
			page.locator( '#billing_first_name' )
		).toBeVisible();
		await expect(
			page.locator( '#billing_last_name' )
		).toBeVisible();
		await expect( page.locator( '#billing_email' ) ).toBeVisible();
		await expect(
			page.locator( '#billing_address_1' )
		).toBeVisible();
		await expect( page.locator( '#billing_city' ) ).toBeVisible();
		await expect(
			page.locator( '#billing_postcode' )
		).toBeVisible();
		await expect( page.locator( '#billing_phone' ) ).toBeVisible();
	} );

	test( 'mixed cart (free + paid): all billing fields shown', async ( {
		page,
	} ) => {
		// Empty cart first.
		await page.goto( '/cart/' );
		while ( ( await page.locator( 'a.remove' ).count() ) > 0 ) {
			await page.locator( 'a.remove' ).first().click();
			await page.waitForLoadState( 'networkidle' );
		}

		// Add free + paid products by ID.
		await page.goto( `/?add-to-cart=${ FREE_VIRTUAL_ID }` );
		await page.waitForLoadState( 'networkidle' );
		await page.goto( `/?add-to-cart=${ PAID_PRODUCT_ID }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/checkout/' );
		await page.waitForLoadState( 'networkidle' );

		// Cart total > 0 so all fields should be shown.
		await expect(
			page.locator( '#billing_address_1' )
		).toBeVisible();
		await expect( page.locator( '#billing_city' ) ).toBeVisible();
		await expect(
			page.locator( '#billing_postcode' )
		).toBeVisible();
		await expect( page.locator( '#billing_phone' ) ).toBeVisible();
	} );

	test( 'free product with shipping: billing fields shown', async ( {
		page,
	} ) => {
		await emptyCartAddProductAndCheckout( page, FREE_SHIPPING_ID );

		// Free but needs shipping — billing fields should still show.
		await expect(
			page.locator( '#billing_address_1' )
		).toBeVisible();
		await expect( page.locator( '#billing_city' ) ).toBeVisible();
		await expect(
			page.locator( '#billing_postcode' )
		).toBeVisible();
		await expect( page.locator( '#billing_phone' ) ).toBeVisible();
	} );
} );
