// import { registerPaymentMethod } from '@woocommerce/blocks-registry';
// import { createElement } from '@wordpress/element';
//
// registerPaymentMethod( {
// 	name: 'test_gateway_two',
// 	label: createElement( 'span', null, 'Test Gateway 2' ),
// 	content: createElement( 'div', null, 'Test Gateway 2' ),
// 	editContent: createElement( 'div', null, 'Test Gateway 2' ),
// 	edit: createElement( 'div', null, 'Test Gateway 2' ),
// 	canMakePayment: () => true,
// 	ariaLabel: 'Test Gateway 2',
// } );

import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'test_gateway_two_data', {} );

const defaultLabel = __(
	'Test Gateway 2',
	'wc-remove-billing-address-on-free-checkout'
);

const label = decodeEntities( settings.title ) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	return decodeEntities( settings.description || '' );
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

/**
 * Dummy payment method config object.
 */
const TestGatewayTwo = {
	name: "test_gateway_two",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TestGatewayTwo );
