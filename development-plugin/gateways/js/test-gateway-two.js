(function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { createElement } = wp.element;

	registerPaymentMethod( {
		name: 'test_gateway_two',
		label: createElement( 'span', null, 'Test Gateway 2' ),
		content: createElement( 'div', null, 'Test Gateway 2' ),
		editContent: createElement( 'div', null, 'Test Gateway 2' ),
		edit: createElement( 'div', null, 'Test Gateway 2' ),
		canMakePayment: () => true,
		ariaLabel: 'Test Gateway 2',
	} );
})();
