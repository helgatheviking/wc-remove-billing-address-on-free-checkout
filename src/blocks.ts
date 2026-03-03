import { subscribe, select } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';

interface PluginSettings {
	no_billing_gateways?: string[];
}

interface PaymentStoreSelectors {
	getActivePaymentMethod: () => string | undefined;
}

const PAYMENT_STORE = 'wc/store/payment';
const PLUGIN_KEY = 'wc-remove-billing-address-on-free-checkout_data';

const settings = getSetting< PluginSettings >( PLUGIN_KEY, {} );
const noBillingGateways: string[] = settings.no_billing_gateways ?? [];

const FIELDS_TO_HIDE: string[] = [
	'billing-address_1',
	'billing-city',
	'billing-postcode',
	'billing-phone',
];

function setFieldsVisibility( hidden: boolean ): void {
	FIELDS_TO_HIDE.forEach( ( id ) => {
		const input = document.getElementById( id );
		const wrapper =
			input?.closest(
				'[class*="address-form__address"], [class*="address-form__city"], [class*="address-form__postcode"], [class*="address-form__phone"]'
			) ?? input?.parentElement;
		if ( wrapper instanceof HTMLElement ) {
			wrapper.style.display = hidden ? 'none' : '';
		}
	} );
}

let lastMethod: string | null = null;

subscribe( () => {
	const activeMethod = select< PaymentStoreSelectors >(
		PAYMENT_STORE
	).getActivePaymentMethod?.();
	if ( activeMethod === lastMethod ) return;
	lastMethod = activeMethod ?? null;

	const shouldHide =
		activeMethod !== undefined &&
		noBillingGateways.includes( activeMethod );
	setFieldsVisibility( shouldHide );
}, PAYMENT_STORE );
