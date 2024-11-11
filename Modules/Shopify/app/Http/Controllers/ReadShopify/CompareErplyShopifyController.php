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
        $debug = $request->get('debug', 0);
        try {
            $sourceVariants = SourceVariant::query()
                ->with(['sourceProduct'])
                ->where('comparisonPending', 1)
                ->where('is_shopify_deleted', 0)
                ->whereNotNull('sku')
                ->limit($limit)
                ->get();
            if ($debug == 1) {
                dd($sourceVariants);
            }
            if ($sourceVariants->isEmpty()) {
                return response()->json(['message' => 'No Pending data found'], 404);
            }

            foreach ($sourceVariants as $sourceVariant) {
                if ($debug == 2) {
                    dd($sourceVariant);
                }
                //check the sku on the erply variants table
                $erplyVariants = Variant::where('code', $sourceVariant->sku)
                    ->orWhere('code2', $sourceVariant->sku)
                    ->orWhere('code3', $sourceVariant->sku)
                    ->get();
                if ($debug == 3) {
                    dd($erplyVariants);
                }
                if ($erplyVariants->isEmpty()) {
                    $sourceVariant->update(['comparisonPending' => 2]);

                    continue;
                }

                if (count($erplyVariants) > 1) {
                    $sourceVariant->update(['comparisonPending' => 3]);

                    continue;
                }

                Variant::where('productID', $erplyVariants[0]->productID)->update([
                    'shopifyVariantId' => $sourceVariant->shopifyVariantId,
                    'shopifyProductId' => $sourceVariant->shopifyParentId,
                    'shopifyInventoryItemId' => $sourceVariant->inventoryItemId,
                ]);

                if ($debug == 4) {
                    dd($erplyVariants);
                }

                $sourceVariant->update([
                    'comparisonPending' => 0,
                    'varinatId' => $erplyVariants[0]->productID,

                ]);

                $sourceVariants->sourceProduct->update([
                    'stockId' => $erplyVariants[0]->parentProductID
                ]);
            }
            return response()->json(['message' => 'Variants updated successfully', 'code' => 200, 'variants' => $sourceVariants]);
        } catch (Exception $th) {
            dd($th);
        }
    }
}
