<?php
// Here you can initialize variables that will be available to your tests

// Ensure WooCommerce is available and ready
if ( class_exists( 'WooCommerce' ) ) {
	WC()->init();
}