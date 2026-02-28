#!/bin/bash

# Setup script to configure payment gateways for testing

# Enable COD gateway
npx wp-env run cli wp option update woocommerce_cod_settings '{"enabled":"yes","title":"Cash on delivery"}' --format=json --user=1

# Enable BACS gateway  
npx wp-env run cli wp option update woocommerce_bacs_settings '{"enabled":"yes","title":"Direct bank transfer"}' --format=json --user=1

# Configure COD to remove billing address fields (default behavior)
# Note: The plugin's default behavior already removes address fields for free checkout

# Configure BACS to keep billing address fields (override default)
npx wp-env run cli wp option update woocommerce_bacs_settings '{"enabled":"yes","title":"Direct bank transfer","kia_free_checkout_remove_fields":[]}' --format=json --user=1

# Configure BACS to have optional fields (for testing)
npx wp-env run cli wp option update woocommerce_bacs_settings '{"enabled":"yes","title":"Direct bank transfer","kia_free_checkout_remove_fields":[],"kia_free_checkout_optional_fields":["billing_company","billing_phone"]}' --format=json --user=1

echo "Payment gateway configuration completed."