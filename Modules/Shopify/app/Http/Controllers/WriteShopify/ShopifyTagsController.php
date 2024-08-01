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
        $tags = SourceProduct::where('shopifyIssuePending', 1)
            ->select('shopifyProductId', 'shopifyIssueTags')
            ->first();

        if (!$tags) {
            return 'No pending products found';
        }

        $shopifyProductId = $tags->shopifyProductId;
        $productId = trim(str_replace('gid://shopify/Product/', '', $shopifyProductId));
        $shopifyIssueTags = $tags->shopifyIssueTags;

        $currentTags = $this->getTags($productId);
        if ($debug == 1) {
            dd($$productId, $currentTags);
        }
        $newTags = array_unique(array_merge($currentTags, explode(',', $shopifyIssueTags)));
        if ($debug == 2) {
            dd($shopifyProductId, $newTags);
        }
        $this->createTags($productId, json_encode($newTags));
        SourceProduct::where('shopifyProductId', $shopifyProductId)
            ->update(['shopifyIssuePending' => 0]);
        return 'Tags updated successfully';
    }
}
