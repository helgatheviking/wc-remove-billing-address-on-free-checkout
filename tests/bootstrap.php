<?php
/**
 * PHPUnit bootstrap file
 *
 * @package kia/wc-remove-billing-address-on-free-checkout
 */

// Composer autoloader must be loaded before WP_MOCK to avoid PHP Fatal error.
require_once __DIR__ . '/../vendor/autoload.php';

// Fix "sh: php: command not found" when running wpunit tests in PhpStorm.
$is_phpstorm = array_reduce( $GLOBALS['argv'] ?? array(), fn( bool $carry, string $arg ) => $carry || str_contains( $arg, 'PhpStorm' ), false );
if ( $is_phpstorm ) {
	define( 'WP_PHP_BINARY', PHP_BINARY );
}
