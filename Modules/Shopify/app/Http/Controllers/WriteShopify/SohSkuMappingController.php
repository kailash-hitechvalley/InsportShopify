<?php

namespace Modules\Shopify\Http\Controllers\WriteShopify;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Shopify\Models\Source\SourceVariant;
use RetailCare\Shopify\Models\Shopify\ShopifySoh;

class SohSkuMappingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $debug = $request->get('debug') ?? 0;
        $limit = $request->get('limit') ?? 20;
        $shopifySoh = ShopifySoh::query()->where('sku', null)->orderby('id', 'asc')->limit($limit)->get();
        if ($debug == 1) {
            dd($shopifySoh);
        }
        foreach ($shopifySoh as $soh) {
            
            $inventory_item_id = 'gid://shopify/InventoryItem/' . $soh->inventory_item_id;

            $sourceVarinats = SourceVariant::query()->where('inventoryItemId', $inventory_item_id)->first();

            if ($debug == 2) {
                dd($sourceVarinats);
            }
            if ($sourceVarinats) {
                $soh->update([
                    'sku' => $sourceVarinats->sku,
                    'shopify_product_id' => $sourceVarinats->shopifyParentId,
                    'shopify_variant_id' => $sourceVarinats->shopifyVariantId
                ]);
            }
        }
        return response()->json('Sku updated successfully');
    }
}
