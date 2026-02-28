/**
 * External dependencies
 */
import * as fs from 'fs';
import * as path from 'path';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

import { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import {getPostContentRendered, setPageContent} from "./general/rest/wp-post";

export type CheckoutType = 'blocks' | 'shortcode';

 async function getCheckoutPostId( requestUtils: RequestUtils ): Promise<number> {

	 // Set checkout page to use shortcode.
	 const pages = await requestUtils.rest( {
		 method: 'GET',
		 path: '/wp/v2/pages',
		 data: { search: 'Checkout', per_page: 100 },
	 } );
	 const checkoutPage = pages.find(
		 ( p: any ) => p.title.rendered.toLowerCase() === 'checkout'
	 );
	 return checkoutPage.id;
}

async function getCheckoutPageContent( requestUtils ): Promise< string > {
	const pageId = await getCheckoutPostId( requestUtils );
	return await getPostContentRendered( 'page', pageId );
}

async function setCheckoutPageContent( requestUtils, postContent: string ) {
	const page_id = await getCheckoutPostId( requestUtils );
	await setPageContent( page_id, postContent );
}

export async function useBlocksCheckout( requestUtils ) {
	const contentPath = path.join(
		__dirname,
		'../../../../_wp-env/blocks-checkout-post-content.txt'
	);
	const postContent = fs.readFileSync( contentPath, 'utf8' );
	await setCheckoutPageContent( requestUtils, postContent );
}

export async function useShortcodeCheckout( requestUtils ) {
	const postContent =
		'<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->';
	await setCheckoutPageContent( requestUtils, postContent );
}

export async function detectCheckoutType( requestUtils ): Promise< CheckoutType > {
	const postContent = await getCheckoutPageContent( requestUtils );

	// Check for blocks checkout indicators
	const blocksCheckoutStrings = [
		'wc-block-checkout',
		'wp-block-woocommerce-checkout',
		'wc-block-components-checkout-place-order-button',
	];

	// Test for blocks checkout
	for ( const htmlString of blocksCheckoutStrings ) {
		if ( postContent.includes( htmlString ) ) {
			return 'blocks';
		}
	}

	// Check for shortcode checkout indicators
	const shortcodeCheckoutElements = [
		'[woocommerce_checkout]',
		'.woocommerce-checkout',
		'#place_order',
		'form[name="checkout"]',
	];

	// Test for shortcode checkout
	for ( const htmlString of shortcodeCheckoutElements ) {
		if ( postContent.includes( htmlString ) ) {
			return 'shortcode';
		}
	}

	// TODO: Maybe throw error if neither detected?
	// Default to shortcode if uncertain
	return 'shortcode';
}

export async function isBlocksCheckout( requestUtils ): Promise< boolean > {
	return ( await detectCheckoutType( requestUtils ) ) === 'blocks';
}

export async function isShortcodeCheckout( requestUtils ): Promise< boolean > {
	return ( await detectCheckoutType( requestUtils ) ) === 'shortcode';
}
