<?php
/**
 * Filter the fields on the shortcode checkout.
 *
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use WC_Payment_Gateway;
use WC_Payment_Gateways;

/**
 * Filters fields in PHP; enqueues JS to re-filter when payment gateway is changed; re-renders the checkout HTML.
 *
 * @phpstan-type Field array{type?:string, label:string, required:bool, class:array<string>, validate?:array<string>, autocomplete?:string, priority:int}
 */
class Checkout {

	/**
	 * Is the current request a checkout page request / ajax checkout request?
	 *
	 * This is used to ensure we only remove the fields at checkout, not at the my-account page.
	 *
	 * @see \WC_AJAX::update_order_review()
	 */
	protected function is_checkout_page(): bool {

		if ( wp_doing_ajax() ) {

			/**
			 * Nonce already checked in {@see \WC_AJAX::update_order_review()}.
			 *
			 * phpcs:disable WordPress.Security.NonceVerification.Recommended
			 */
			$wc_ajax = isset( $_REQUEST['wc-ajax'] ) && is_string( $_REQUEST['wc-ajax'] )
				? sanitize_key( wp_unslash( $_REQUEST['wc-ajax'] ) )
				: null;
			if ( in_array( $wc_ajax, array( 'checkout', 'update_order_review' ), true ) ) {
				return true;
			}
		}

		if ( ! class_exists( CartCheckoutUtils::class ) ) {
			return false;
		}

		return CartCheckoutUtils::is_checkout_page();
	}

	/**
	 * Default fields to remove (the original plugin behavior).
	 *
	 * Each gateway settings page allows customising which fields are removed. If there has been no customisation
	 * done, use this list.
	 *
	 * @return string[]
	 */
	protected function get_default_billing_fields_to_remove_for_free_checkout(): array {
		return array(
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'billing_country',
			'billing_phone',
		);
	}

	/**
	 * Get the chosen payment gateway at checkout.
	 *
	 * @return ?string The gateway ID, or null if not determinable.
	 */
	protected function get_chosen_payment_gateway(): ?string {
		if ( ! WC()->session ) {
			return null;
		}

		$chosen = WC()->session->get( 'chosen_payment_method' );

		// Also check POST data (AJAX checkout updates send it here).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $chosen ) && ! empty( $_POST['payment_method'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$chosen = sanitize_text_field( wp_unslash( $_POST['payment_method'] ) );
		}

		return $chosen ?: null;
	}

	/**
	 * Get the (saved) fields to remove for a specific gateway.
	 *
	 * Falls back to the default list if no per-gateway settings are saved.
	 *
	 * @param string $gateway_id The payment gateway ID.
	 * @return string[] Field keys to remove.
	 */
	protected function get_fields_to_remove_for_gateway( string $gateway_id ): array {
		$gateway = $this->get_gateway_instance( $gateway_id );

		if ( ! $gateway ) {
			return $this->get_default_billing_fields_to_remove_for_free_checkout();
		}

		$saved = $gateway->get_option( 'kia_free_checkout_remove_fields' );

		if ( ! is_array( $saved ) || empty( $saved ) ) {
			return $this->get_default_billing_fields_to_remove_for_free_checkout();
		}

		return $saved;
	}

	/**
	 * Get the (saved) fields to make optional (shown but not required) for a specific gateway.
	 *
	 * @param string $gateway_id The payment gateway ID.
	 * @return string[] Field keys to make optional.
	 */
	protected function get_fields_to_make_optional_for_gateway( string $gateway_id ): array {
		$gateway = $this->get_gateway_instance( $gateway_id );

		if ( ! $gateway ) {
			return array();
		}

		$saved = $gateway->get_option( 'kia_free_checkout_optional_fields' );

		if ( ! is_array( $saved ) ) {
			return array();
		}

		return $saved;
	}

	/**
	 * Check if all products in the cart are virtual or downloadable.
	 *
	 * @return bool True if all cart items are virtual/downloadable, false otherwise.
	 */
	protected function cart_contains_only_virtual_products(): bool {
		if ( ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( ! $product || ! ( $product->is_virtual() || $product->is_downloadable() ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get a WC_Payment_Gateway instance by ID.
	 *
	 * @param string $gateway_id The gateway ID.
	 * @return WC_Payment_Gateway|null
	 */
	protected function get_gateway_instance( string $gateway_id ): ?WC_Payment_Gateway {
		$gateways = WC_Payment_Gateways::instance()->payment_gateways();
		return $gateways[ $gateway_id ] ?? null;
	}

	/**
	 * Determine are we working with a gateway whose fields will be removed.
	 *
	 * @return ?bool `null` when the gateway cannot be determined.
	 */
	protected function chosen_gateway_requires_billing_fields(): ?bool {
		$chosen_payment_gateway = $this->get_chosen_payment_gateway();
		if ( ! $chosen_payment_gateway ) {
			return null;
		}
		return $this->gateway_requires_billing_fields( $chosen_payment_gateway );
	}

	/**
	 * Check whether the chosen gateway declares that it does not need billing fields.
	 *
	 * A gateway opts out by setting the public property `$require_billing_fields = false`,
	 * or by being the built-in Cash on Delivery gateway (`cod`).
	 *
	 * @param string $gateway_id The payment gateway ID.
	 * @return bool
	 */
	protected function gateway_requires_billing_fields( string $gateway_id ): bool {
		if ( 'cod' === $gateway_id ) {
			return false;
		}

		$gateway = $this->get_gateway_instance( $gateway_id );

		if ( $gateway && property_exists( $gateway, 'require_billing_fields' ) && false === $gateway->require_billing_fields ) {
			return false;
		}

		return true;
	}

	/**
	 * Remove or make optional billing fields at checkout.
	 *
	 * Two independent reasons can cause billing address fields to be removed:
	 *
	 * 1. **Free checkout**: cart total is $0 and no shipping is needed.
	 *    Uses per-gateway settings (or defaults) to determine which fields to remove/make optional.
	 *
	 * 2. **Gateway does not require billing**: the chosen gateway has `require_billing_fields = false`
	 *    (or is Cash on Delivery). The default address fields are removed regardless of cart total.
	 *
	 * This method is hooked on `woocommerce_billing_fields` so that the fragment replacement
	 * in {@see rerender_billing_fields()} picks up the changes during AJAX checkout updates.
	 *
	 * @hooked woocommerce_billing_fields
	 *
	 * @param array<string, Field>&array<string,mixed> $fields The billing fields. Some are custom.
	 * @return array<string, Field>&array<string,mixed>
	 */
	public function remove_billing_address_fields_for_free_checkout( array $fields ): array {

		if ( ! $this->is_checkout_page() ) {
			return $fields;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $fields;
		}

		if ( ! WC()->cart ) {
			return $fields;
		}

		$gateway_id = $this->get_chosen_payment_gateway();

		$is_virtual         = ! WC()->cart->needs_shipping();
		$is_free_virtual    = $this->is_free_cart() && $is_virtual;
		$gateway_no_billing = $gateway_id && ! $this->gateway_requires_billing_fields( $gateway_id );

		// Leave fields untouched.
		if ( ! $is_virtual && wc_ship_to_billing_address_only() ) {
			return $fields;
		}
		if ( ! $this->is_free_cart() && ! $gateway_no_billing ) {
			return $fields;
		}

		// Determine which fields to remove / make optional.
		if ( $is_free_virtual && $gateway_id ) {
			// Free checkout with a known gateway — use per-gateway settings.
			$fields_to_remove        = $this->get_fields_to_remove_for_gateway( $gateway_id );
			$fields_to_make_optional = $this->get_fields_to_make_optional_for_gateway( $gateway_id );
		} elseif ( $is_free_virtual ) {
			// Free checkout, no gateway selected — use defaults.
			$fields_to_remove        = $this->get_default_billing_fields_to_remove_for_free_checkout();
			$fields_to_make_optional = array();
		} else {
			// Gateway doesn't require billing (but cart is not free).
			$fields_to_remove        = $this->get_default_billing_fields_to_remove_for_free_checkout();
			$fields_to_make_optional = array();
		}

		/**
		 * Filter the fields to remove for free checkout.
		 *
		 * @since 1.0.1
		 * @param string[]    $fields_to_remove The billing field keys to remove.
		 * @param string|null $gateway_id       The chosen payment gateway ID.
		 * @return string[]
		 */
		$fields_to_remove = (array) apply_filters(
			'wc_billing_fields_to_remove_for_free_checkout',
			$fields_to_remove,
			$gateway_id
		);

		/**
		 * Filter the fields to make optional (shown but not required) for free checkout.
		 *
		 * @since 2.0.0
		 * @param string[]    $fields_to_make_optional The billing field keys to make optional.
		 * @param string|null $gateway_id              The chosen payment gateway ID.
		 * @return string[]
		 */
		$fields_to_make_optional = (array) apply_filters(
			'wc_billing_fields_to_make_optional_for_free_checkout',
			$fields_to_make_optional,
			$gateway_id
		);

		// Remove fields completely.
		foreach ( $fields_to_remove as $field ) {
			if ( isset( $fields[ $field ] ) ) {
				unset( $fields[ $field ] );
			}
		}

		// Make fields optional (shown but not required).
		foreach ( $fields_to_make_optional as $field ) {
			if ( isset( $fields[ $field ] ) ) {
				$fields[ $field ]['required'] = false;
			}
		}

		return $fields;
	}

	/**
	 * Is the order total zero?
	 */
	protected function is_free_cart(): bool {

		if ( ! function_exists( 'WC' ) || is_null( WC()->cart ) ) {
			return false;
		}

		return 0.0 === floatval( WC()->cart->get_total( 'edit' ) );
	}

	/**
	 * Check does the cart need shipping.
	 */
	protected function is_needs_shipping(): bool {

		if ( ! function_exists( 'WC' ) || is_null( WC()->cart ) ) {
			return false;
		}

		return WC()->cart->needs_shipping();
	}

	/**
	 * Remove shipping fields when cart contains only virtual/downloadable products.
	 *
	 * WooCommerce handles this partially via needs_shipping(), but explicitly removing
	 * the shipping section ensures a cleaner checkout form.
	 *
	 * @hooked woocommerce_checkout_fields
	 *
	 * @param array $fields All checkout fields.
	 * @return array
	 */
	public function remove_shipping_fields_for_virtual_products( $fields ) {

		// Don't modify anything when the admin is viewing settings.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $fields;
		}

		if ( ! WC()->cart ) {
			return $fields;
		}

		if ( ! $this->cart_contains_only_virtual_products() ) {
			return $fields;
		}

		if ( ! $this->is_checkout_page() ) {
			return $fields;
		}

		if ( isset( $fields['shipping'] ) ) {
			unset( $fields['shipping'] );
		}

		return $fields;
	}

	/**
	 * Register the JavaScript files used for WooCommerce.
	 *
	 * The {@see assets/shortcode-checkout.js} script fires the `update_checkout` trigger when a selected gateway is changed
	 *
	 * @hooked wp_enqueue_scripts
	 */
	public function enqueue_scripts(): void {

		if ( 'production' === wp_get_environment_type() ) {
			$version = '2.0.0';
		} else {
			$version = (string) time();
		}

		$script_url = plugins_url( '/assets/shortcode-checkout.js', dirname( __DIR__ ) );

		wp_enqueue_script( 'wc-remove-billing-address-on-free-checkout', $script_url, array( 'jquery' ), $version, false );
	}

	/**
	 * Re-render the billing fields when the checkout JavaScript `update_cart` event is fired.
	 *
	 * The fragments usually only contain `.woocommerce-checkout-review-order-table` and
	 * `.woocommerce-checkout-payment`. Other sections of the checkout can be added to the response to be replaced too.
	 *
	 * @hooked woocommerce_update_order_review_fragments
	 * @see woocommerce_checkout_update_order_review
	 *
	 * @param array<string, string> $fragments CSS selector : HTML to set at checkout.
	 * @return  array<string, string>
	 */
	public function rerender_billing_fields( array $fragments ): array {

		$checkout = WC()->checkout();

		ob_start();

		wc_get_template( 'checkout/form-billing.php', array( 'checkout' => $checkout ) );

		$woocommerce_billing_fields = ob_get_clean();

		if ( $this->is_free_cart() ) {
			$woocommerce_billing_fields = $this->set_billing_fragment_title_to_shipping_only( $woocommerce_billing_fields );
		}

		$fragments['.woocommerce-billing-fields'] = $woocommerce_billing_fields;

		return $fragments;
	}

	/**
	 * Replace "Billing & Shipping" with just "Shipping".
	 *
	 * @param string $shortcode_checkout_html HTML from the shortcode checkout.
	 */
	protected function set_billing_fragment_title_to_shipping_only( string $shortcode_checkout_html ): string {
		return str_replace( '<h3>Billing &amp; Shipping</h3>', '<h3>Shipping</h3>', $shortcode_checkout_html );
	}

	/**
	 * Replace "Billing & Shipping" with just "Shipping" for free carts, i.e. when WooCommerce has already removed
	 * the shipping fields and are only displaying the billing to use as shipping.
	 *
	 * @hooked do_shortcode_tag
	 *
	 * @param string $output Shortcode output.
	 * @param string $tag Shortcode name.
	 * @param array  $attr Shortcode attributes array, can be empty if the original arguments string cannot be parsed.
	 * @param array  $m Regular expression match array.
	 */
	public function set_shortcode_fragment_title( $output, $tag, $attr, $m ) {

		if ( 'woocommerce_checkout' !== $tag ) {
			return $output;
		}

		if ( ! $this->is_free_cart() ) {
			return $output;
		}

		return $this->set_billing_fragment_title_to_shipping_only( $output );
	}

	/**
	 *
	 *
	 * @hooked do_shortcode_tag
	 *
	 * @param string       $output Shortcode output.
	 * @param string       $tag Shortcode name.
	 * @param array<mixed> $attr Shortcode attributes array, can be empty if the original arguments string cannot be parsed.
	 * @param array        $m Regular expression match array.
	 */
	public function set_shortcode_ship_to_different_address( string $output, string $tag, array $attr, array $m ) {
		if ( 'woocommerce_checkout' !== $tag ) {
			return $output;
		}

		if ( $this->get_chosen_payment_gateway() && $this->chosen_gateway_requires_billing_fields() ) {
			return $output;
		}

		return $this->replace_ship_to_different_address_checked( $output );
	}

	/**
	 * If we remove the billing fields and require shipping, the option "[ ] Ship to a different address?" should be removed.
	 *
	 * @see woocommerce/templates/checkout/form-shipping.php
	 *
	 * @param array<string, string> $fragments CSS selector : HTML to set at checkout.
	 */
	public function rerender_shipping_fields( array $fragments ): array {

		$checkout = WC()->checkout();

		ob_start();

		wc_get_template( 'checkout/form-shipping.php', array( 'checkout' => $checkout ) );

		$woocommerce_shipping_fields = ob_get_clean();

		if ( $this->is_needs_shipping()
			&&
			( $this->is_free_cart() || ! $this->chosen_gateway_requires_billing_fields() )
		) {
			$woocommerce_shipping_fields = $this->replace_ship_to_different_address_checked( $woocommerce_shipping_fields );
		}

		/**
		 * Remove `woocommerce-additional-fields`, otherwise it duplicates.
		 */
		$woocommerce_shipping_fields = preg_replace( '/<div class="woocommerce-additional-fields">[\s\S]*/m', '', $woocommerce_shipping_fields );

		$fragments['.woocommerce-shipping-fields'] = $woocommerce_shipping_fields;

		return $fragments;
	}

	/**
	 * "Ship to a different address?" should not be an option if we have removed billing address but there is a product to ship.
	 *
	 * ```
	 * <h3 id="ship-to-different-address">
	 *  <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
	 *   <input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" checked="checked" type="checkbox" name="ship_to_different_address" value="1"> <span>Ship to a different address?</span>
	 *  </label>
	 * </h3>
	 * ```
	 *
	 * @param string $shipping_fields_html The shortcode checkout HTML shipping form.
	 */
	protected function replace_ship_to_different_address_checked( string $shipping_fields_html ): string {

		/**
		 * `ship_to_different_address` is used in {@see \WC_Checkout::get_posted_data()} so is required.
		 */
		$hidden_checked_checkbox = '<input id="ship-to-different-address-checkbox" checked="checked" type="checkbox" name="ship_to_different_address" value="1" style="display:none;">';

		return preg_replace(
			'/(<h3 id="ship-to-different-address">)([\S\s]*?)(<\/h3>)/m',
			'$1' . $hidden_checked_checkbox . __( 'Shipping', 'woocommerce' ) . '$3',
			$shipping_fields_html
		);
	}

	/**
	 * If we're going to remove the billing fields, we must show the shipping fields
	 *
	 * @see wc_ship_to_billing_address_only()
	 * @see woocommerce_ship_to_destination
	 * @see woocommerce/templates/checkout/form-shipping.php
	 * @hooked woocommerce_ship_to_different_address_checked
	 *
	 * @param int $val The WooCommerce template passes `1`|`0`, but it seems plausible other plugins might return bools.
	 * @return int|bool|string Will be passed to {@see checked()} to generate the "is-checked" checkbox input HTML.
	 */
	public function force_display_shipping_form( $val ) {

		if ( ! $this->is_needs_shipping() ) {
			return $val;
		}

		if ( $this->is_needs_shipping() && ( $this->is_free_cart() || ! $this->chosen_gateway_requires_billing_fields() ) ) {
			return (int) true;
		}

		return $val;
	}
}
