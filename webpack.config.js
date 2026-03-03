const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'wc-remove-billing-address-on-free-checkout-blocks': path.resolve(
			__dirname,
			'src/blocks.ts'
		),
	},
	externals: {
		...defaultConfig.externals,
		'@woocommerce/settings': [ 'wc', 'wcSettings' ],
	},
};
