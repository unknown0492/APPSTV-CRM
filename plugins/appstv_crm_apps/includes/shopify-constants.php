<?php

// SHOPICY VALUES
define( 'SHOPIFY_LOCATION_ID_SINGAPORE', '73488171185' );


// Shopify Customer Entity Attribute Keys
define( 'SHOPIFY_CUSTOMER_ID', 'id' );
define( 'SHOPIFY_CUSTOMER_EMAIL', 'email' );
define( 'SHOPIFY_CUSTOMER_CREATED_AT', 'created_at' );
define( 'SHOPIFY_CUSTOMER_UPDATED_AT', 'updated_at' );
define( 'SHOPIFY_CUSTOMER_FIRST_NAME', 'first_name' );
define( 'SHOPIFY_CUSTOMER_LAST_NAME', 'last_name' );
define( 'SHOPIFY_CUSTOMER_STATE', 'state' );
define( 'SHOPIFY_CUSTOMER_CURRENCY', 'currency' );
define( 'SHOPIFY_CUSTOMER_EMAIL_MARKETING_CONSENT', 'email_marketing_consent' );


// Shopify Order Entity Attribute Keys
define( 'SHOPIFY_ORDER_CREATED_AT', "created_at" );
define( 'SHOPIFY_ORDER_CURRENCY', "currency" );
define( 'SHOPIFY_ORDER_CONTACT_EMAIL', "contact_email" );
define( 'SHOPIFY_ORDER_EMAIL', "email" );
define( 'SHOPIFY_ORDER_FINANCIAL_STATUS', "financial_status" );
define( 'SHOPIFY_ORDER_FULFILLMENT_STATUS', "fulfillment_status" );
define( 'SHOPIFY_ORDER_PROCESSED_AT', "processed_at" );
define( 'SHOPIFY_ORDER_PRODUCTS', "line_items" );
define( 'SHOPIFY_ORDER_SHIPPING_LINES', "shipping_lines" );
define( 'SHOPIFY_ORDER_SHIPPING_METHOD_NAME', "shipping_lines" );
define( 'SHOPIFY_ORDER_TOTAL_LINE_ITEMS_PRICE', "total_line_items_price" );
define( 'SHOPIFY_ORDER_SUBTOTAL_PRICE', "subtotal_price" );
define( 'SHOPIFY_ORDER_TAXES_INCLUDED', "taxes_included" );
define( 'SHOPIFY_ORDER_TEST', "test" );
define( 'SHOPIFY_ORDER_TOTAL_PRICE', "total_price" );
define( 'SHOPIFY_ORDER_TOTAL_DISCOUNTS', "total_discounts" );
define( 'SHOPIFY_ORDER_TOTAL_SHIPPING_PRICE', "total_shipping_price_set" );
define( 'SHOPIFY_ORDER_TOTAL_TAX', "total_tax" );
define( 'SHOPIFY_ORDER_TOTAL_WEIGHT', "total_weight" );
define( 'SHOPIFY_ORDER_UPDATED_AT', "updated_at" );
define( 'SHOPIFY_ORDER_CANCEL_REASON', "cancel_reason" );
define( 'SHOPIFY_ORDER_CANCELLED_AT', "cancelled_at" );
define( 'SHOPIFY_ORDER_CUSTOMER', "customer" );

// Shopify Order Meta Entity Attribute Keys
define( 'SHOPIFY_ORDER_ID', "id" );
define( 'SHOPIFY_ORDER_ORDER_NUMBER', "order_number" );
define( 'SHOPIFY_ORDER_ORDER_NAME', "name" );
define( 'SHOPIFY_ORDER_CONFIRMED', "confirmed" );
define( 'SHOPIFY_ORDER_ORDER_STATUS_URL', "order_status_url" );
define( 'SHOPIFY_ORDER_DISCOUNT_CODES', "discount_codes" );
define( 'SHOPIFY_ORDER_PAYMENT_GATEWAY_NAMES', "payment_gateway_names" );

// Shopify Order Address Entity Attribute Keys
define( 'SHOPIFY_BILLING_ADDRESS', "billing_address" );
define( 'SHOPIFY_SHIPPING_ADDRESS', "shipping_address" );
define( 'SHOPIFY_FIRST_NAME', "first_name" );
define( 'SHOPIFY_LAST_NAME', "last_name" );
define( 'SHOPIFY_NAME_ON_ORDER', "name" );
define( 'SHOPIFY_PHONE', "phone" );
define( 'SHOPIFY_CONTACT', "contact" );
define( 'SHOPIFY_ADDRESS1', "address1" );
define( 'SHOPIFY_ADDRESS2', "address2" );
define( 'SHOPIFY_CITY', "city" );
define( 'SHOPIFY_STATE', "state" );
define( 'SHOPIFY_PROVINCE', "province" );
define( 'SHOPIFY_ZIP', "zip" );
define( 'SHOPIFY_COUNTRY', "country" );
define( 'SHOPIFY_COUNTRY_CODE', "country_code" );
define( 'SHOPIFY_LATITUDE', "latitude" );
define( 'SHOPIFY_LONGITUDE', "longitude" );

// Shopify Product Entity Attribute Keys
define( 'SHOPIFY_PRODUCT_ID', 'id' );
define( 'SHOPIFY_PRODUCT_SKU', 'sku' );
define( 'SHOPIFY_PRODUCT_BARCODE', 'barcode' );
define( 'SHOPIFY_PRODUCT_WEIGHT', 'grams' );

// Shopify ProductMeta Entiry Attributes
define( 'SHOPIFY_PRODUCT_HANDLE', 'handle' ); 
define( 'SHOPIFY_PRODUCT_TITLE', 'title' );
define( 'SHOPIFY_PRODUCT_INVENTORY_COUNT', 'inventory_quantity' );
define( 'SHOPIFY_PRODUCT_PRICE', 'price' );
define( 'SHOPIFY_PRODUCT_REQUIRES_SHIPPING', 'requires_shipping' ); // boolean
define( 'SHOPIFY_PRODUCT_TAXABLE', 'taxable' ); // boolean
define( 'SHOPIFY_PRODUCT_INVENTORY_ITEM_ID', 'inventory_item_id' ); 

// User account related constants
define( '_NEW_ACCOUNT_ACTIVATION_METHOD_SHOPIFY_VERIFIED', "verified_at_shopify" );

// Configurations related constants
define( 'EMAIL_CUSTOMER_WITH_NEW_ACCOUNT_CREDENTIALS', "email_customer_with_new_account_credentials" );


?>