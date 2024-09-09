<?php

use Illuminate\Support\Facades\Route;
use Modules\GetShopifyData\Http\Controllers\GetShopifyDataController;
use Modules\GetShopifyData\Http\Controllers\GetSOHFromShopifyController;
use Modules\GetShopifyData\Http\Controllers\ShopifyController\GetProductController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('getshopifydata', GetShopifyDataController::class)->names('getshopifydata');
});

Route::get('/get-shopify-products', [GetProductController::class, 'getProducts']);
Route::get('/get-shopify-variants', [GetProductController::class, 'getProductVariants']);

Route::get('/get-shopify-locations', [GetProductController::class, 'getLocations']);

Route::get('/get-shopify-soh', [GetSOHFromShopifyController::class, 'getSoh']);




Route::get('/get-variants-product-null', [GetProductController::class, 'getProductVariantsNull']);
