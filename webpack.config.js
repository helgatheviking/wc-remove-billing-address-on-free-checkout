const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const path = require( 'path' );

const wcDepMap = {
	'@woocommerce/blocks-registry': [ 'wc', 'wcBlocksRegistry' ],
	'@woocommerce/settings': [ 'wc', 'wcSettings' ],
	'@woocommerce/block-data': [ 'wc', 'wcBlocksData' ],
	'@woocommerce/shared-context': [ 'wc', 'wcSharedContext' ],
	'@woocommerce/shared-hocs': [ 'wc', 'wcSharedHocs' ],
	'@woocommerce/price-format': [ 'wc', 'priceFormat' ],
	'@woocommerce/blocks-checkout': [ 'wc', 'blocksCheckout' ],
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings': 'wc-settings',
	'@woocommerce/block-settings': 'wc-settings',
	'@woocommerce/block-data': 'wc-blocks-data-store',
	'@woocommerce/shared-context': 'wc-shared-context',
	'@woocommerce/shared-hocs': 'wc-shared-hocs',
	'@woocommerce/price-format': 'wc-price-format',
	'@woocommerce/blocks-checkout': 'wc-blocks-checkout',
};

const requestToExternal = ( request ) => {
	if ( wcDepMap[ request ] ) {
		return wcDepMap[ request ];
	}
};

const requestToHandle = ( request ) => {
	if ( wcHandleMap[ request ] ) {
		return wcHandleMap[ request ];
	}
};

const mainConfig = {
	...defaultConfig,
	name: 'main',
	entry: {
		'wc-remove-billing-address-on-free-checkout-blocks': path.resolve(
			__dirname,
			'src/blocks.ts'
		),
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin( {
			requestToExternal,
			requestToHandle,
		} ),
	],
};

const devGatewaysConfig = {
	...defaultConfig,
	name: 'dev-gateways',
	entry: {
		'test-gateway-one': path.resolve(
			__dirname,
			'development-plugin/gateways/js/test-gateway-one.tsx'
		),
		'test-gateway-two': path.resolve(
			__dirname,
			'development-plugin/gateways/js/test-gateway-two.tsx'
		),
	},
	output: {
		filename: '[name].js',
		path: path.resolve( __dirname, 'development-plugin/build' ),
	},
	externals: {
		...defaultConfig.externals,
		'@woocommerce/blocks-registry': [ 'wc', 'wcBlocksRegistry' ],
		'@wordpress/element': [ 'wp', 'element' ],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin' &&
				plugin.constructor.name !== 'CleanWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin( {
			requestToExternal,
			requestToHandle,
		} ),
	],
};

module.exports = [ mainConfig, devGatewaysConfig ];
