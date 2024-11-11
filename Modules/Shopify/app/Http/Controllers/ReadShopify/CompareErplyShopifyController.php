<?php

namespace Modules\Shopify\Http\Controllers\ReadShopify;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Shopify\Models\ErplyModel\Variant;
use Modules\Shopify\Models\Source\SourceProduct;
use Modules\Shopify\Models\Source\SourceVariant;

class CompareErplyShopifyController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->get('limit', 20);
        try {
            $sourceVariants = SourceVariant::query()
                ->with(['sourceProduct'])
                ->where('comparisonPending', 1)
                ->where('is_shopify_deleted', 0)
                ->whereNotNull('sku')
                ->limit($limit)
                ->get();

            foreach ($sourceVariants as $sourceVariant) {
                DB::beginTransaction();
                //check the sku on the erply variants table
                $erplyVariants = Variant::query()
                    ->where('code', $sourceVariant->sku)
                    ->orWhere('code2', $sourceVariant->sku)
                    ->orWhere('code3', $sourceVariant->sku)
                    ->get();
                if ($erplyVariants->isEmpty()) {
                    $sourceVariant->update(['comparisonPending' => 2]);
                    DB::commit();
                    continue;
                }
                if ($erplyVariants->count() > 1) {
                    $sourceVariant->update(['comparisonPending' => 3]);
                    DB::commit();
                    continue;
                }
                $erplyVariants->update([
                    'shopifyVariantId' => $sourceVariant->shopifyVariantId,
                    'shopifyProductId' => $sourceVariant->shopifyParentId,
                    'shopifyInventoryItemId' => $sourceVariant->inventoryItemId,
                ]);
                
                $sourceVariant->update([
                    'comparisonPending' => 0,
                    'varinatId' => $erplyVariants->productID,

                ]);
                $sourceVariants->sourceProduct->update([
                    'stockId' => $erplyVariants->parentProductID
                ]);
                DB::commit();

                return response()->json(['message' => 'Variants updated successfully', 'code' => 200, 'variants' => $sourceVariants]);
            }
        } catch (Exception $th) {
            dd($th);
        }
    }
}