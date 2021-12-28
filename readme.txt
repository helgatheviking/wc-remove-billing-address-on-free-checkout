=== Remove WooCommerce Billing Address Fields for Free Checkout ===
Contributors: helgatheviking
Donate link: https://www.paypal.com/fundraiser/charity/1451316
Tags: woocommerce, checkout, billing, address
Requires at least: 5.0.0
Tested up to: 5.8.2
Stable tag: 1.0.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==

It can be a hassle to fill out your billing address if you aren't actually paying any money. This plugin removes the billing address fields from WooCommerce checkout when the cart total is 0, ie: Free. 

Specifically the following fields are removed:

1. billing_address_1
1. billing_address_2
1. billing_city
1. billing_state
1. billing_postcode
1. billing_country
1. billing_phone

That's it!

= Support =

Support is handled in the [WordPress forums](http://wordpress.org/support/plugin/remove-woocommerce-billing-address-fields-for-free-checkout). Please note that support is very limited. Before posting a question, please confirm that the problem still exists with a default theme and with all other plugins disabled.

Please report any bugs, errors, warnings, code problems to [Github](https://github.com/helgatheviking/wc-remove-billing-address-fields/issues)

== Installation ==

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. The simplified checkout form.

== Frequently Asked Questions ==

= How to exclude billing phone field from removal? =

To achieve this you can add the following snippet to your theme's `functions.php` or via the [Code Snippets plugin](wordpress.org/plugins/code-snippets/).

`
/**
 * Do not remove billing phone
 *
 * @param array   $fields    The billing fields we are removing
 * @return array
 */
function kia_do_not_remove_billing_phone( $fields ) {
    return array_diff( $fields, array( 'billing_phone' ) );
}
add_filter( 'wc_billing_fields_to_remove_for_free_checkout', 'kia_do_not_remove_billing_phone' );
`

== Changelog ==

= 2021-12-28 - 1.0.1 =
* New: Introduce `wc_billing_fields_to_remove_for_free_checkout` filter.
* Fix: Do not remove address when shipping is required. Fixes "Please enter an address to continue." error on checkout.

= 1.0 =
* Initial release.
