<?php
return [
    'connection' => env('SHOPIFY_DB_CONNECTION', 'mysql_shopify'),
    'shopifyConnection' => [
        'live' => [
            'url' => env('SHOPIFY_STORE_URL'),
            'version' => env('SHOPIFY_API_VERSION'),
            'accessToken' => env('SHOPIFY_API_TOKEN'),
        ],
        'staging' => [
            'url' => env('SHOPIFY_STORE_URL_STAGING'),
            'version' => env('SHOPIFY_API_VERSION'),
            'accessToken' => env('SHOPIFY_API_TOKEN_STAGING'),
        ],
    ],
    'models' => [
        'shopify' => [
            'SHOPIFY_PRODUCTS' => 'shopify_products',
            'SHOPIFY_CUSTOMER' => 'shopify_customers',
            'SHOPIFY_CURSOR' => 'shopify_cursor',
            'SHOPIFY_ORDER' => 'shopify_orders',
            'SHOPIFY_ORDER_PRODUCTS' => 'shopify_order_products',
            'SHOPIFY_ORDER_DELIVERY' => 'shopify_order_deliveries',
            'SHOPIFY_ORDER_REFUND' => 'shopify_order_refunds',
            'SHOPIFY_ORDER_REFUND_ITEMS' => 'shopify_order_refund_items',
            'SHOPIFY_WEBHOOK' => 'shopify_webhooks',
            'SHOPIFY_SOH' => 'shopify_soh',
        ],
        'erply' => [
            'connection' => env('ERPLY_DB_CONNECTION', 'mysql_erply'),
            'ERPLY_MATRIX_PRODUCTS' => 'erply_products',
            'ERPLY_VARIANTS' => 'newsystem_product_variations',
            'ERPLY_CUSTOMERS' => 'erply_customers',
        ]
    ],

    'settings' => [
        'GenericCustomerHelper' => env('GENERIC_CUSTOMER_HELPER', 0),
        'isloyaltypointenable' => env('LOYALTY_POINT_ENABLE', 0),
        'shopifyproductupadte' => env('SHOPIFY_PRODUCT_UPDATE', 0),
        'checkCustomerInDBforIsUpdate' => env('CHECK_CUSTOMER_IN_DB', 0),
        'setSohPendingOnErply' => env('SET_SOH_PENDING_ON_ERPLY', 0),
        'enableShopifyErrorLog' => env('ENABLE_ERROR_LOG', 0),
        'verifyCsrfTokenEnable' => env('VERIFY_CSRF_TOKEN_ENABLE', 0),
        'checkCustomerInDBforIsUpdateUrl' => 'https://sexyland.synccare.io/sexyland/public/update-erply-customer-from-shopify',
        'syncSkuWhileSohUpdateWebhook' => env('SYNC_SKU_WHILE_SOH_WEBHOOK', 1),
        'SHOPIFY_VARIATION_MODEL' => env('SHOPIFY_VARIATION_MODEL', 'Modules\Shopify\Models\Source\SourceVariant'),
        'FakeAddressEnable' => env('FAKE_ADDRESS_ENABLE', 1),

    ],


    'webhook_urls' => [
        '/shopify-webhook/inventory-level-update',
    ]
];
