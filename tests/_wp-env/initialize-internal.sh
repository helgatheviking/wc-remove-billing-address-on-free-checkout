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
	
	# Create a free virtual product ($0.00) 
	FREE_PRODUCT_ID=$(wp wc product create \
		--name="Free Virtual Product" \
		--type="simple" \
		--regular_price="0.00" \
		--virtual=true \
		--status="publish" \
		--porcelain)
	echo "Created free virtual product with ID: $FREE_PRODUCT_ID"
	
	# Create a paid simple product ($19.99)
	PAID_PRODUCT_ID=$(wp wc product create \
		--name="Paid Simple Product" \
		--type="simple" \
		--regular_price="19.99" \
		--status="publish" \
		--porcelain)
	echo "Created paid simple product with ID: $PAID_PRODUCT_ID"
	
	# Create a free product that requires shipping (for testing edge cases)
	FREE_SHIPPING_PRODUCT_ID=$(wp wc product create \
		--name="Free Product with Shipping" \
		--type="simple" \
		--regular_price="0.00" \
		--virtual=false \
		--weight="1" \
		--status="publish" \
		--porcelain)
	echo "Created free shipping product with ID: $FREE_SHIPPING_PRODUCT_ID"
	
	echo "WooCommerce setup complete!"
else
	echo "WooCommerce is not active, skipping WooCommerce setup."
fi