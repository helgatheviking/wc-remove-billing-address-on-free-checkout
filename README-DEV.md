# WC Remove Billing Address - Development Environment

This document describes the development environment setup and testing approach for the WooCommerce Remove Billing Address plugin.

## Plugin Overview

This plugin removes billing address fields from WooCommerce checkout when:
1. Cart total is $0.00 AND
2. Shipping is not needed (virtual products)

**Important**: The plugin only works with **shortcode checkout** (`[woocommerce_checkout]`). It does NOT work with **WooCommerce blocks checkout** because blocks use their own field management system, not the `woocommerce_billing_fields` filter.

## Development Environment

### Prerequisites

- PHP 7.4+
- Node.js 20+
- Composer
- Docker (for wp-env)

### Quick Start

```bash
# Install dependencies
composer install
npm install

# Start WordPress environment
npm run wp-env

# Run tests
composer test          # PHPUnit/Codeception tests
npm run test:e2e       # Playwright E2E tests

# Code quality
composer cs            # PHP_CodeSniffer + PHPStan
composer cs-fix        # Auto-fix issues
```

## Testing Strategy

### 1. Unit Tests (`tests/unit/`)

Testing the plugin's core function `remove_billing_address_fields_for_free_checkout()` with WP_Mock:

- **Free cart + no shipping** → removes address fields
- **Paid cart** → keeps all fields  
- **Free cart + shipping needed** → keeps all fields

### 2. WordPress Integration Tests (`tests/wpunit/`)

Testing within a real WordPress environment with WooCommerce loaded.

### 3. End-to-End Tests (`tests/e2e-pw/`)

**The main testing focus** - Comprehensive Playwright tests covering real user scenarios:

#### Shortcode Checkout Tests (`checkout-shortcode.spec.ts`)

Tests the plugin's **intended behavior** on classic `[woocommerce_checkout]`:

1. **Free virtual product**: Address fields are removed, only name+email remain
2. **Complete checkout with minimal info**: Can place order with just name+email
3. **Paid product**: All billing fields shown as normal
4. **Mixed cart (free + paid)**: All fields shown (cart total > 0)
5. **Free product requiring shipping**: All fields shown (shipping needed)

#### Blocks Checkout Tests (`checkout-blocks.spec.ts`)

Tests demonstrating the plugin **does NOT work** with blocks checkout:

1. **Free product in blocks**: Address fields still shown (plugin has no effect)
2. **Blocks still requires full address**: Can't complete order without address
3. **Paid product in blocks**: Fields shown as expected

#### Plugin Activation Tests (`plugin-activation.spec.ts`)

Basic smoke tests ensuring the plugin activates without PHP errors.

## Architecture

### File Structure

```
wc-remove-billing-address-on-free-checkout/
├── wc-remove-billing-address-on-free-checkout.php  # Main plugin file
├── .wp-env.json                                    # WordPress environment config
├── playwright.config.ts                           # Playwright test configuration
├── composer.json                                   # PHP dependencies & scripts
├── package.json                                    # Node dependencies & scripts
├── tests/
│   ├── unit/                                       # WP_Mock unit tests
│   ├── wpunit/                                     # WordPress integration tests
│   ├── e2e-pw/                                     # Playwright E2E tests
│   │   ├── checkout-shortcode.spec.ts              # Shortcode checkout tests
│   │   ├── checkout-blocks.spec.ts                 # Blocks checkout tests
│   │   └── plugin-activation.spec.ts               # Basic activation tests
│   └── _wp-env/                                    # WordPress setup scripts
└── .github/workflows/                              # CI/CD workflows
```

### Key Concepts

1. **Single-file plugin**: No classes, no autoloader - just one function hooked to `woocommerce_billing_fields`
2. **Filter-based**: Uses WooCommerce's `woocommerce_billing_fields` filter (shortcode only)
3. **Conditional logic**: Only removes fields when cart total = $0.00 AND no shipping needed
4. **Shortcode limitation**: Blocks checkout uses different field management

## WordPress Environment

The wp-env configuration includes:

- **WooCommerce plugin** mapped from Composer
- **Test products** created by initialization scripts:
  - Free virtual product ($0.00)
  - Paid simple product ($19.99) 
  - Free product requiring shipping ($0.00 + weight)
- **Checkout page switching** between shortcode and blocks in tests

## CI/CD Workflows

- **E2E Tests**: Playwright tests against wp-env
- **Unit/Coverage**: PHPUnit + Codeception with coverage reporting
- **Code Quality**: PHP_CodeSniffer + PHPStan
- **Auto-fixing**: PHPCBF auto-commits fixes
- **Dependencies**: Dependabot updates
- **AI Review**: Claude Code automated PR reviews

## Development Guidelines

### Running Tests Locally

```bash
# Start environment
npm run wp-env

# Wait for WordPress to be ready
curl -s http://localhost:8080

# Run unit tests
composer test

# Run E2E tests  
npm run test:e2e

# Run specific test file
npx playwright test tests/e2e-pw/checkout-shortcode.spec.ts
```

### Test Data

E2E tests create their own test products using WooCommerce REST API to avoid conflicts between parallel test runs.

### Debugging

```bash
# View WordPress logs
npx wp-env logs

# Run tests with debug output
npx playwright test --debug
composer test -- --debug

# Access WordPress admin
open http://localhost:8080/wp-admin
# admin / password
```

## Important Notes

1. **Plugin Limitations**: Only works with shortcode checkout, not blocks
2. **Test Focus**: E2E tests are the primary validation method
3. **Real-world Testing**: Tests use actual WooCommerce checkout flows
4. **Cross-browser**: Tests run on Chromium, Firefox, and WebKit
5. **CI Environment**: All tests run automatically on pull requests

This setup provides comprehensive testing coverage while keeping the simple nature of the single-file plugin intact.
