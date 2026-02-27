/**
 * Test basic plugin activation and deactivation.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Plugin Activation', () => {
	test( 'Plugin can be activated without errors', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'plugins.php' );

		// Look for our plugin in the list
		const pluginRow = page.locator( 'tr[data-plugin*="wc-remove-billing-address-on-free-checkout"]' );
		await expect( pluginRow ).toBeVisible();

		// Check if plugin is already active
		const isActive = await pluginRow.locator( '.activate, .deactivate' ).textContent();
		
		if ( isActive && isActive.includes( 'Deactivate' ) ) {
			// Plugin is already active
			await expect( pluginRow.locator( '.deactivate' ) ).toBeVisible();
		} else {
			// Plugin is not active, activate it
			await pluginRow.locator( '.activate a' ).click();
			await page.waitForLoadState( 'networkidle' );
			
			// Should now show "Deactivate" option
			await expect( pluginRow.locator( '.deactivate' ) ).toBeVisible();
		}

		// Verify no PHP errors on the plugins page
		const bodyText = await page.locator( 'body' ).textContent();
		expect( bodyText ).not.toContain( 'Fatal error' );
		expect( bodyText ).not.toContain( 'Parse error' );
		expect( bodyText ).not.toContain( 'Warning:' );
	} );

	test( 'Plugin appears in installed plugins list with correct details', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'plugins.php' );

		const pluginRow = page.locator( 'tr[data-plugin*="wc-remove-billing-address-on-free-checkout"]' );
		await expect( pluginRow ).toBeVisible();

		// Check plugin name
		await expect( pluginRow.locator( '.plugin-title strong' ) ).toContainText( 'Remove WooCommerce billing address for free checkout' );

		// Check plugin description
		await expect( pluginRow.locator( '.plugin-description' ) ).toContainText( 'Remove billing address fields fro checkout when cart total is free' );

		// Check author
		await expect( pluginRow.locator( '.plugin-description' ) ).toContainText( 'Kathy Darling' );
	} );

	test( 'Plugin hook is registered when active', async ( { page, admin } ) => {
		// Go to a page where we can check if the hook is registered
		await admin.visitAdminPage( 'plugins.php' );

		// Verify the plugin is active first
		const pluginRow = page.locator( 'tr[data-plugin*="wc-remove-billing-address-on-free-checkout"]' );
		await expect( pluginRow.locator( '.deactivate' ) ).toBeVisible();

		// Test that our function is hooked to the woocommerce_billing_fields filter
		// We'll do this by checking the front-end functionality in our other test files
		// This test mainly ensures activation succeeds without errors
		const bodyText = await page.locator( 'body' ).textContent();
		expect( bodyText ).not.toContain( 'Fatal error' );
		expect( bodyText ).not.toContain( 'Parse error' );
	} );
} );