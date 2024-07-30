<?php

namespace Modules\GetShopifyData\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\GetShopifyData\Services\CommonService;
use Modules\GetShopifyData\Services\ShopifyGetService;
use Modules\Shopify\Traits\ShopifyTrait;

class GetSOHFromShopifyController extends Controller
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
    public function getSoh(Request $request)
    {

        try {
            $id = $request->get('id') ?? null;
            $debug = $request->get('debug') ?? 0;
            $limit = $request->get('limit') ?? 3;

            $response = $this->service->getSoh($id, $limit);

            if ($debug == 1) {
                dd($response);
            }

            if ($response->data->products->edges) {
                $products = $response->data->products->edges;
                if ($debug == 2) {
                    dd($products);
                }
                $clientCode = $this->getClientCode();
                $isLive = $this->live;
                $currentCursor = $this->getCursor($clientCode, 'SOH', $isLive) ?? '';

                foreach ($products as $productEdge) {
                    $product = $productEdge->node;
                    $productId = $product->id;
                    $productTitle = $product->title;

                    foreach ($product->variants->edges as $variantEdge) {
                        $variant = $variantEdge->node;
                        $variantId = $variant->id;
                        $variantTitle = $variant->title;
                        $inventoryItemId = $variant->inventoryItem->id;

                        foreach ($variant->inventoryItem->inventoryLevels->edges as $inventoryLevelEdge) {
                            $inventoryLevel = $inventoryLevelEdge->node;
                            $available = $inventoryLevel->available;
                            $locationId = $inventoryLevel->location->id;
                            $locationName = $inventoryLevel->location->name;
                            $inventoryLevelUpdatedAt = ($inventoryLevel->updatedAt);
                            DB::table('shopify_soh')->updateOrInsert(
                                [
                                    'locationID' => $locationId,
                                    'inventoryID' => $inventoryItemId,
                                ],
                                [
                                    'productID' => $productId,
                                    'clientCode' => $clientCode,
                                    'isLive' => $isLive,
                                    'variationID' => $variantId,
                                    'name' => $productTitle,
                                    'sku' => $variantTitle,
                                    'locationName' => $locationName,
                                    'available' => $available,
                                    'lastModified' => $inventoryLevelUpdatedAt,
                                    'cursors' => $currentCursor,
                                ]
                            );
                        }
                    }
                }

                echo "Data inserted/updated successfully";
            } else {
                print_r($response);
                echo "no products found";
            }
        } catch (Exception $e) {
            DB::rollBack();
            dd($e);
        }
    }
}
