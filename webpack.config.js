const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	externals: {
		...defaultConfig.externals,
		'@woocommerce/settings': [ 'wc', 'wcSettings' ],
	},
};
