import { subscribe, select } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';

const PAYMENT_STORE = 'wc/store/payment';
const PLUGIN_KEY = 'wc-remove-billing-address-on-free-checkout_data';

const settings = getSetting( PLUGIN_KEY, {} );
const noBillingGateways = settings.no_billing_gateways || [];

const FIELDS_TO_HIDE = [
	'billing-address_1',
	'billing-city',
	'billing-postcode',
	'billing-phone',
];

function setFieldsVisibility( hidden ) {
	FIELDS_TO_HIDE.forEach( ( id ) => {
		const input = document.getElementById( id );
		const wrapper =
			input?.closest( '[class*="address-form__address"], [class*="address-form__city"], [class*="address-form__postcode"], [class*="address-form__phone"]' ) ??
			input?.parentElement;
		if ( wrapper ) {
			wrapper.style.display = hidden ? 'none' : '';
		}
	} );
}

let lastMethod = null;

subscribe( () => {
	const activeMethod = select( PAYMENT_STORE ).getActivePaymentMethod?.();
	if ( activeMethod === lastMethod ) return;
	lastMethod = activeMethod;

	const shouldHide = noBillingGateways.includes( activeMethod );
	setFieldsVisibility( shouldHide );
}, PAYMENT_STORE );
