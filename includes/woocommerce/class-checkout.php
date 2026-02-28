<?php

namespace KIA\WC_Remove_Billing_Address_On_Free_Checkout\WooCommerce;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use WC_Payment_Gateway;
use WC_Payment_Gateways;

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
	 * @param array $fields The billing fields.
	 * @return array
	 */
	public function remove_billing_address_fields_for_free_checkout( $fields ) {

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $fields;
		}

		if ( ! WC()->cart ) {
			return $fields;
		}

		$g = $GLOBALS;

		if ( ! $this->is_checkout_page() ) {
			return $fields;
		}

		$gateway_id = $this->get_chosen_payment_gateway();

		$cart_total         = floatval( WC()->cart->get_total( 'edit' ) );
		$is_free_virtual    = ( 0.0 === $cart_total && ! WC()->cart->needs_shipping() );
		$gateway_no_billing = $gateway_id && ! $this->gateway_requires_billing_fields( $gateway_id );

		// Neither condition met — leave fields untouched.
		if ( ! $is_free_virtual && ! $gateway_no_billing ) {
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
	 * @hooked wp_enqueue_scripts
	 *
	 * This JS fires the `update_checkout` trigger when a selected gateway is changed
	 */
	public function enqueue_scripts(): void {

		if ( 'production' === wp_get_environment_type() ) {
			$version = '2.0.0';
		} else {
			$time    = time();
			$version = "{$time}";
		}

		// $script_url = plugins_url( 'build/checkout/index.js', WP_PLUGIN_DIR . '/bh-wc-venmo-gateway/bh-wc-venmo-gateway.php' );
		$script_url = plugins_url( '/assets/shortcode-checkout.js', dirname( __DIR__ ) );

		// wp_enqueue_script( 'bh-wp-account-and-login-ux-woocommerce-checkout', plugin_dir_url( __FILE__ ) . '/../assets/shortcode-checkout.js', array( 'jquery' ), $version, false );
		wp_enqueue_script( 'bh-wp-account-and-login-ux-woocommerce-checkout', $script_url, array( 'jquery' ), $version, false );
	}

	/**
	 * rerender the billing fields on js update_cart
	 *
	 * The fragments usually only contain `.woocommerce-checkout-review-order-table` and `.woocommerce-checkout-payment`.
	 *
	 * @hooked woocommerce_update_order_review_fragments
	 * @see woocommerce_checkout_update_order_review
	 *
	 * @param $fragments
	 * @return mixed
	 */
	public function rerender_billing_fields( $fragments ) {

		$checkout = WC()->checkout();

		ob_start();

		wc_get_template( 'checkout/form-billing.php', array( 'checkout' => $checkout ) );

		$woocommerce_billing_fields = ob_get_clean();

		// TODO: Maybe manipulate the shipping fragment.
		// E.g. "Ship to a different address?" should not be an option if we have removed billing address but there is a product to ship.

		$fragments['.woocommerce-billing-fields'] = $woocommerce_billing_fields;

		return $fragments;
	}


	/**
	 * Remove billing fields per gateway.
	 *
	 * @deprecated 2.0.0 Logic merged into {@see remove_billing_address_fields_for_free_checkout()}
	 *             which hooks on `woocommerce_billing_fields` so the AJAX fragment
	 *             replacement picks up the changes.
	 *
	 * @hooked woocommerce_checkout_fields
	 *
	 * @param array $fields All checkout fields.
	 * @return array
	 */
	public function remove_billing_fields_per_gateway( array $fields ): array {
		// No-op: logic now lives in remove_billing_address_fields_for_free_checkout().
		return $fields;
	}
}
