(function( $ ) {
	'use strict';

	$( document ).ready(function() {

		// Track the last known payment method so we only fire update_checkout
		// when the gateway actually changes (not on every blur/change of #payment).
		var lastPaymentMethod = $( 'input[name="payment_method"]:checked' ).val() || '';

		// When the selected payment gateway changes, trigger an AJAX checkout
		// update so the billing fields fragment is re-rendered with the correct
		// fields for the new gateway.
		$( 'body' ).on( 'change', 'input[name="payment_method"]', function () {
			var newMethod = $( this ).val();

			if ( newMethod === lastPaymentMethod ) {
				return;
			}
			lastPaymentMethod = newMethod;

			// Display loading indicator while refreshing fields.
			$( '.woocommerce-billing-fields__field-wrapper' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			window.checkoutReloadedBy = 'wc-remove-billing-address-on-free-checkout';

			// Fires AJAX update — the server will return new billing field fragments.
			$( document.body ).trigger( 'update_checkout' );
		});

		$( document.body ).on( 'updated_checkout', function() {

			// Remove the loading indicator.
			$( '.woocommerce-billing-fields__field-wrapper' ).unblock();

			// Re-initialize Select2 on any new select elements.
			$( 'select' ).select2();

			// Only run cleanup after updates triggered by this plugin.
			if ( window.checkoutReloadedBy !== 'wc-remove-billing-address-on-free-checkout' ) {
				return;
			}
			window.checkoutReloadedBy = null;
		});

	});

})( jQuery );
