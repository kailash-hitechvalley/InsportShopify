<?php

use Illuminate\Support\Facades\Route;
use Modules\GetShopifyData\Http\Controllers\ShopifyController\GetProductController;
use Modules\Shopify\Http\Controllers\Middleware\Erply\CollectionController;
use Modules\Shopify\Http\Controllers\Middleware\Erply\ErplySohController;
use Modules\Shopify\Http\Controllers\Middleware\Erply\ImagesController;
use Modules\Shopify\Http\Controllers\Middleware\Erply\LocationController;
use Modules\Shopify\Http\Controllers\Middleware\Erply\PriceController;
use Modules\Shopify\Http\Controllers\Middleware\Erply\ProductController;
use Modules\Shopify\Http\Controllers\Middleware\Erply\SohController;
use Modules\Shopify\Http\Controllers\ReadShopify\OrderController;
use Modules\Shopify\Http\Controllers\ReadShopify\ShopifyController;
use Modules\Shopify\Http\Controllers\ReadShopify\ShopifyProductsController;
use Modules\Shopify\Http\Controllers\ReadShopify\ShopifyWebHookController;
use Modules\Shopify\Http\Controllers\ViewController\OrderViewController;
use Modules\Shopify\Http\Controllers\WriteShopify\GiftCardController;
use Modules\Shopify\Http\Controllers\WriteShopify\ShopifyCustomerController;
use Modules\Shopify\Http\Controllers\WriteShopify\ShopifyTagsController;
use Modules\Shopify\Http\Controllers\WriteShopify\SourceProductController;
use Modules\Shopify\Http\Controllers\WriteShopify\SourceCollectionController;
use Modules\Shopify\Http\Controllers\WriteShopify\SourceImageController;
use Modules\Shopify\Http\Controllers\WriteShopify\SourcePriceController;
use Modules\Shopify\Http\Controllers\WriteShopify\SourceSohController;


Route::get('refunds', [ShopifyController::class, 'getRefund']);
Route::get('getorders', [OrderController::class, 'getOrders']);
Route::get('getrefunds', [OrderController::class, 'getRefunds']);

# routes for shopify  push
Route::get('push-products', [SourceProductController::class, 'index']);
Route::get('push-collections', [SourceCollectionController::class, 'index']);
Route::get('push-images', [SourceImageController::class, 'index']);
Route::get('add-variants-media', [SourceImageController::class, 'addvariantsToMedia']);
Route::get('push-product-soh', [SourceSohController::class, 'index']);
Route::get('push-price', [SourcePriceController::class, 'index']);

# move products details from erplay to module

Route::get('get-products', [ProductController::class, 'getProducts']);

Route::get('get-category', [CollectionController::class, 'getCategory']);

Route::get('get-locations', [LocationController::class, 'getLocation']);

Route::get('get-images', [ImagesController::class, 'index']);

// Route::get('get-soh', [SohController::class, 'index']);
Route::get('get-soh-erply', [ErplySohController::class, 'index']);

Route::get('get-price', [PriceController::class, 'index']);
#Web Hooks

Route::post('shopify/webhooks/orders', [ShopifyWebHookController::class, 'handleOrderwebHooks']);

Route::get('get-tags', [ProductController::class, 'getTags']);



#Shopify Products

Route::get('getShopifyProducts', [ShopifyProductsController::class, 'getProducts']);
Route::get('getVariantsProducts', [ShopifyProductsController::class, 'getVariantsProducts']);

Route::get('delete-laravel-logs', [OrderViewController::class, 'deleteLaravelLogs']);
#Route::post('webhook-product', [ShopifyProductsController::class, 'handleProductwebHooks']);
Route::post('webhook-product', [ShopifyProductsController::class, 'handleProductwebHooks']);

#get customers from the Shopify
Route::get('/get-shopify-customers', [ShopifyCustomerController::class, 'index']);

#push the Gift cards to the Shopify
Route::get('/push-gift-card', [GiftCardController::class, 'index']);


#issue tags
Route::get('/create-shopify-tags', [ShopifyTagsController::class, 'storeTags']);
// Route::get('/check-issue-tags', [ShopifyTagsController::class, 'checkIssueTags']);
Route::get('/resync-shopify-products', [GetProductController::class, 'getissueProducts']);
