#!/bin/bash

PLUGIN_SLUG=$1;
# Print the script name.
echo "Running " $(basename "$0") " for " $PLUGIN_SLUG;

mkdir /var/www/html/wp-content/uploads || true;
chmod a+w /var/www/html/wp-content/uploads;

echo "wp plugin activate --all"
wp plugin activate --all

# Install jq to manipulate json (optional output of WP CLI commands)
if command -v jq &> /dev/null; then
	echo "jq is already installed."
else
	echo "jq not found, installing..."
	sudo apk add jq
fi

echo "Set up pretty permalinks for REST API."
wp rewrite structure /%year%/%monthnum%/%postname%/ --hard;

# WooCommerce setup
if wp plugin is-active woocommerce; then
	echo "Setting up WooCommerce..."

	# Create WooCommerce pages if they don't exist
	wp wc tool run install_pages

	# Configure WooCommerce settings
	wp option update woocommerce_store_address "123 Test Street"
	wp option update woocommerce_store_city "Test City"
	wp option update woocommerce_default_country "US:CA"
	wp option update woocommerce_store_postcode "90210"
	wp option update woocommerce_currency "USD"
	wp option update woocommerce_calc_taxes "yes"

	# Create test products
	echo "Creating test products..."

	create_product_if_not_exists() {
		local name="$1"
		shift
		if wp wc product list --user=admin --search="$name" --format=count | grep -q "^0$"; then
			wp wc product create --user=admin --name="$name" "$@"
		else
			echo "Product '$name' already exists, skipping."
		fi
	}

	create_product_if_not_exists "Digital Product" --type=simple --regular_price=9.99 --virtual=true
	create_product_if_not_exists "Free Digital Product" --type=simple --regular_price=0.00 --virtual=true
	create_product_if_not_exists "Shipped Product" --type=simple --regular_price=9.99 --virtual=false
	create_product_if_not_exists "Free Shipped Product" --type=simple --regular_price=0.00 --virtual=false
	create_product_if_not_exists "Regular Product" --type=simple --regular_price=9.99 --virtual=false

  wp option set woocommerce_coming_soon no

  wp wc payment_gateway update cheque --enabled=1
#  wp wc payment_gateway update test_gateway_one --enabled="yes"
#  wp wc payment_gateway update test_gateway_two --enabled="yes"


  wp option patch update test_gateway_one enabled yes
  wp option patch update test_gateway_two enabled yes


	echo "WooCommerce setup complete!"
else
	echo "WooCommerce is not active, skipping WooCommerce setup."
fi


