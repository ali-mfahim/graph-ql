<?php

return [

    "shopify" => [
        // Store Name: Momyom - Beta 2
        // App Name : Beta - Testing 2
        "access_token" => env("SHOPIFY_ACCESS_TOKEN", ""),
        "app_key" => env("SHOPIFY_APP_KEY", ""),
        "app_secret" => env("SHOPIFY_APP_SECRET", ""),
        "domain" => env("SHOPIFY_DOMAIN", ""),
        "base_url" => env("SHOPIFY_BASE_URL", ""),
        "app_version" => env("SHOPIFY_API_VERSION", ""),
        "store_currency" => env("STORE_CURRENCY", "AED"),


    ],
    "currency" => [
        "base_url" => "https://openexchangerates.org/api/",
        "token" => env("CURRENCY_TOKEN", "40f99d19403742058794063fdb122ca9"),
    ],
];
