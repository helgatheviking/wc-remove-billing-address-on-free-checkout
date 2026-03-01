(function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { createElement } = wp.element;

	registerPaymentMethod( {
		name: 'test_gateway_one',
		label: createElement( 'span', null, 'Test Gateway 1' ),
		content: createElement( 'div', null, 'Test Gateway 1' ),
		editContent: createElement( 'div', null, 'Test Gateway 1' ),
		edit: createElement( 'div', null, 'Test Gateway 1' ),
		canMakePayment: () => true,
		ariaLabel: 'Test Gateway 1',
		supports: {
			features: ['products'],
		},
	} );
})();
