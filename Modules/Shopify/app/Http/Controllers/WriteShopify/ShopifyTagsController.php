<?php

namespace Modules\Shopify\Http\Controllers\WriteShopify;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\GetShopifyData\Services\CommonService;
use Modules\GetShopifyData\Services\ShopifyGetService;
use Modules\Shopify\Models\Source\SourceProduct;
use Modules\Shopify\Traits\ShopifyTrait;

class ShopifyTagsController extends Controller
{
    protected ShopifyGetService $service;
    protected CommonService $comSer;
    protected $live = 1;
    use ShopifyTrait;
    public function __construct(ShopifyGetService $service, CommonService $comSer)
    {
        $this->service = $service;
        $this->comSer = $comSer;
    }
    public function storeTags(Request $request)
    {
        $debug = $request->debug ?? 0;

        $tags = SourceProduct::query()
            ->where('shopifyIssuePending', 1)
            ->select('shopifyProductId', 'shopifyIssueTags')
            ->limit(3)
            ->get();
        if ($debug == 1) {
            dd($tags);
        }
        if (!$tags) {
            return response()->json('No pending products found');
        }
        $responses = [];
        foreach ($tags as  $tag) {

            $shopifyProductId = $tag->shopifyProductId;
            $shopifyIssueTags = $tag->shopifyIssueTags;
            $currentTags = $this->getTags($shopifyProductId);

            if ($debug == 2) {
                dd($shopifyProductId, $currentTags);
            }
            $newTags = array_unique(
                array_merge(
                    $currentTags,
                    explode(',', $shopifyIssueTags)
                )
            );
            $responses[] = $this->createTags($shopifyProductId, json_encode($newTags));

            if ($debug == 3) {
                dd($responses);
            }

            SourceProduct::query()
                ->where('shopifyProductId', $shopifyProductId)
                ->update(['shopifyIssuePending' => 0]);
        }
        return response()->json(['Tags updated successfully', 'response' => $responses]);
    }
    public function checkIssueTags(Request $request)
    {
        $debug = $request->debug ?? 0;
        $limit = $request->limit ?? 20;

        try {
            $products = SourceProduct::query()
                ->whereNotNull('shopifyIssueTags')
                ->where('productTags', 'NOT LIKE', '%erply%')
                ->where('sohPendingProcess', 8)
                ->limit($limit)
                ->get();
            if ($debug == 1) {
                dd($products);
            }
            if (count($products) <= 0) {
                return response()->json('No pending products found');
            }

            foreach ($products as  $product) {
                $product->update([
                    'sohPendingProcess' => 1
                ]);
            }

            return response()->json('Tags updated successfully');
        } catch (\Exception $e) {

            return response()->json($e->getMessage());
        }
    }
}
