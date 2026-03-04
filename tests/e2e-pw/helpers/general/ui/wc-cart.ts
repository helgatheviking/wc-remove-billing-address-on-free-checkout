/**
 * Set the customer billing and shipping details via REST so they are prefilled at checkout.
 *
 * presumably, a "reset session" function would just delete the cookie.
 */

/**
 * External dependencies
 */
import { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import config from '../../../../../playwright.config';

export async function addProductToCartByName( page: Page, productName: string, quantity: number = 1 ): Promise< void > {
	const baseURL: string = config.use.baseURL!;
	const currentUrl = page.url();

	if ( ! currentUrl.startsWith( baseURL ) ) {
		await page.goto( `${ baseURL }/shop` );
		await page.waitForLoadState( 'networkidle' );
	}

	const result = await page.evaluate(
		async ( { name, qty } ) => {
			// @ts-ignore
			const nonce: string = window?.wcBlocksMiddlewareConfig?.storeApiNonce ?? window?.wcSettings?.storeApiNonce ?? '';

			// Use the Store API to search for the product by name to get its id.
			const searchResponse = await fetch(
				`/wp-json/wc/store/v1/products?search=${ encodeURIComponent( name ) }&per_page=1`,
				{ headers: { Nonce: nonce } }
			);

			if ( ! searchResponse.ok ) {
				return { ok: false, status: searchResponse.status, body: { message: 'Product search failed' } };
			}

			const products = await searchResponse.json();
			if ( ! products.length ) {
				return { ok: false, status: 404, body: { message: `Product not found: ${ name }` } };
			}

			// Use the product id with the native WooCommerce Store API cart endpoint.
			const cartResponse = await fetch( '/wp-json/wc/store/v1/cart/add-item', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', Nonce: nonce },
				body: JSON.stringify( { id: products[ 0 ].id, quantity: qty } ),
			} );

			const cartBody = await cartResponse.json();
			return { ok: cartResponse.ok, status: cartResponse.status, body: cartBody };
		},
		{ name: productName, qty: quantity }
	);

	if ( result.status !== 201 && result.status !== 200 ) {
		throw new Error(
			`Failed to add product to cart: ${ result.status } ${ JSON.stringify( result.body ) }`
		);
	}
}
