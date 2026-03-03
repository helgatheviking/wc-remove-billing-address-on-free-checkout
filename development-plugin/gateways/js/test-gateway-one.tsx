// // import { createElement } from '@wordpress/element';
//
// // import { registerPaymentMethod } from '../../../vendor/woocommerce/woocommerce/plugins/woocommerce/client/blocks/assets/js/blocks-registry/payment-methods/registry';
// import { registerPaymentMethod } from '@woocommerce/blocks-registry';
// // import { getSetting } from '../../../vendor/woocommerce/woocommerce/packages/js/data/src/settings/selectors';
// import { getSetting } from '@woocommerce/settings';
// import { decodeEntities } from '@wordpress/html-entities';
// import { useState, useCallback, useEffect } from '@wordpress/element';
// import { __ } from '@wordpress/i18n';
//
// // const useState = window.wp.element.useState;
//
//
// interface TestGatewayOneSettings {
// 	title?: string;
// 	description?: string;
// 	supports?: string[];
// 	test_gateway_one_icon_url?: string;
// }
//
// interface PaymentMethodLabelProps {
// 	components: {
// 		PaymentMethodLabel: React.FC< { text: string } >;
// 		PaymentMethodIcons: React.FC< { icons: Array< { id: string; src: string; alt: string } > } >;
// 	};
// }
//
// interface EmitResponseType {
// 	responseTypes: {
// 		SUCCESS: string;
// 		ERROR: string;
// 		FAIL: string;
// 	};
// 	noticeContexts: {
// 		PAYMENTS: string;
// 	};
// }
//
// interface PaymentMethodContentProps {
// 	eventRegistration: {
// 		onPaymentSetup: ( callback: () => { type: string; meta?: { paymentMethodData: Record< string, string > }; message?: string } ) => () => void;
// 	};
// 	emitResponse: EmitResponseType;
// }
//
// const settings: TestGatewayOneSettings = getSetting( 'test_gateway_one', {} );
//
// var label = '';
// const settingsTitle: string = decodeEntities( settings.title || '' );
// if(settingsTitle !== '' && settingsTitle !== 'TestGatewayOne' && settingsTitle !== 'test_gateway_one' ) {
// 	label = settingsTitle;
// }
//
// /**
//  * Content component — shows description and TestGatewayOne username input.
//  */
// const TestGatewayOneContent: React.FC< PaymentMethodContentProps > = ( {
// 																  eventRegistration,
// 																  emitResponse,
// 															  } ) => {
// 	const [ testGatewayOneUsername, setTestGatewayOneUsername ] = useState( '' );
// 	const { onPaymentSetup } = eventRegistration;
//
// 	const onUsernameChange = useCallback(
// 		( e: React.ChangeEvent< HTMLInputElement > ) => {
// 			setTestGatewayOneUsername( e.target.value );
// 		},
// 		[]
// 	);
//
// 	useEffect( () => {
// 		const unsubscribe = onPaymentSetup( () => {
// 			if ( ! testGatewayOneUsername.trim() ) {
// 				return {
// 					type: emitResponse.responseTypes.ERROR,
// 					message: __(
// 						'Please enter your TestGatewayOne username.',
// 						'bh-wc-test-gateway-one-gateway'
// 					),
// 				};
// 			}
// 			return {
// 				type: emitResponse.responseTypes.SUCCESS,
// 				meta: {
// 					paymentMethodData: {
// 						'_customer-test-gateway-one-username': testGatewayOneUsername.trim(),
// 					},
// 				},
// 			};
// 		} );
// 		return unsubscribe;
// 	}, [ testGatewayOneUsername, onPaymentSetup, emitResponse.responseTypes ] );
//
// 	return (
// 		<div className="bh-wc-test-gateway-one-gateway-blocks-checkout">
// 			{ settings.description && (
// 				<p className="wc-block-components-checkout-step__description">
// 					{ decodeEntities( settings.description ) }
// 				</p>
// 			) }
// 			<div className="bh-wc-test-gateway-one-gateway-username-field">
// 				<label htmlFor="test-gateway-one-username-input">
// 					{ __( 'Enter your TestGatewayOne username:', 'bh-wc-test-gateway-one-gateway' ) }
// 					<abbr className="required" title="required">*</abbr>
// 				</label>
// 				<input
// 					id="test-gateway-one-username-input"
// 					type="text"
// 					value={ testGatewayOneUsername }
// 					onChange={ onUsernameChange }
// 					placeholder={ __( 'TestGatewayOne username', 'bh-wc-test-gateway-one-gateway' ) }
// 					maxLength={ 255 }
// 					required
// 					className="wc-block-components-text-input"
// 					aria-label={ __( 'TestGatewayOne username', 'bh-wc-test-gateway-one-gateway' ) }
// 				/>
// 			</div>
// 		</div>
// 	);
// };
//
// /**
//  * Label component with optional icon.
//  */
// const TestGatewayOneLabel: React.FC< PaymentMethodLabelProps > = ( { components } ) => {
// 	const { PaymentMethodLabel } = components;
// 	return (
// 		<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
// 	{ settings.test_gateway_one_icon_url && (
// 		<img
// 			src={ settings.test_gateway_one_icon_url }
// 		alt={ label }
// 		style={ { height: '24px' } }
// 		/>
// 	) }
// 	<PaymentMethodLabel text={ label } />
// 	</div>
// );
// };
//
// /**
//  * Edit component for the block editor — static preview.
//  */
// const TestGatewayOneEdit: React.FC = () => {
// 	return (
// 		<div className="bh-wc-test-gateway-one-gateway-blocks-checkout">
// 			{ settings.description && (
// 					<p className="wc-block-components-checkout-step__description">
// 						{ decodeEntities( settings.description || '' ) }
// 			</p>
// 	) }
// 	<div className="bh-wc-test-gateway-one-gateway-username-field">
// 	<label htmlFor="test-gateway-one-username-input-preview">
// 		{ __( 'Enter your TestGatewayOne username:', 'bh-wc-test-gateway-one-gateway' ) }
// 	<abbr className="required" title="required">*</abbr>
// 		</label>
// 		<input
// 	id="test-gateway-one-username-input-preview"
// 	type="text"
// 	placeholder={ __( 'TestGatewayOne username', 'bh-wc-test-gateway-one-gateway' ) }
// 	disabled
// 	className="wc-block-components-text-input"
// 		/>
// 		</div>
// 		</div>
// );
// };
//
// // registerPaymentMethod( {
// // 	name: 'test_gateway_one',
// // 	label: createElement( 'span', null, 'Test Gateway 1' ),
// // 	content: createElement( 'div', null, 'Test Gateway 1' ),
// // 	editContent: createElement( 'div', null, 'Test Gateway 1' ),
// // 	edit: createElement( 'div', null, 'Test Gateway 1' ),
// // 	canMakePayment: () => true,
// // 	ariaLabel: 'Test Gateway 1',
// // 	supports: {
// // 		features: [ 'products' ],
// // 	},
// // } );
//
// const testGatewayOnePaymentMethod = {
// 	name: 'test_gateway_one',
// 	label: <TestGatewayOneLabel components={ { PaymentMethodLabel: ( { text }: { text: string } ) => <>{ text }</>, PaymentMethodIcons: () => null } } />,
// 	content: <TestGatewayOneContent eventRegistration={ { onPaymentSetup: () => () => {} } } emitResponse={ { responseTypes: { SUCCESS: 'success', ERROR: 'error', FAIL: 'fail' }, noticeContexts: { PAYMENTS: 'wc/checkout/payments' } } } />,
// 	edit: <TestGatewayOneEdit />,
// 		canMakePayment: (): boolean => true,
// 		ariaLabel: label,
// 		supports: {
// 		features: settings.supports || [],
// 	},
// };
//
// registerPaymentMethod( testGatewayOnePaymentMethod );


// import { registerPaymentMethod } from '@woocommerce/blocks-registry';
// import { createElement } from '@wordpress/element';
//
// registerPaymentMethod( {
// 	name: 'test_gateway_one',
// 	label: createElement( 'span', null, 'Test Gateway 1' ),
// 	content: createElement( 'div', null, 'Test Gateway 1' ),
// 	editContent: createElement( 'div', null, 'Test Gateway 1' ),
// 	edit: createElement( 'div', null, 'Test Gateway 1' ),
// 	canMakePayment: () => true,
// 	ariaLabel: 'Test Gateway 1',
// } );



import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'test_gateway_one_data', {} );

const defaultLabel = __(
	'Test Gateway 1',
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
const TestGatewayOne = {
	name: "test_gateway_one",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( TestGatewayOne );
