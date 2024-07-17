<?php

namespace Modules\GetShopifyData\Http\Controllers\ShopifyController;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\GetShopifyData\App\Services\CommonService;
use Modules\GetShopifyData\App\Services\ShopifyGetService;
use Modules\Shopify\App\Models\Source\SourceProduct;
use Modules\Shopify\App\Models\Source\SourceVariant;
use Modules\Shopify\App\Traits\ShopifyTrait;

class GetProductController extends Controller
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

    public function getProducts(Request $request)
    {
        try {
            $id = $request->get('id') ?? null;
            $debug = $request->get('debug') ?? 0;

            $response = $this->service->getShopifyProducts($id);

            if ($debug == 1) {
                dd($response);
            }


            if ($response->data->products->edges) {
                $products = $response->data->products->edges;
                if ($debug == 2) {
                    dd($products);
                }

                $lastKey = array_key_last($products);

                foreach ($products as $key => $product) {

                    $cursor = $product->cursor;
                    if ($key === $lastKey) {
                        $this->comSer->saveCursor($cursor, 'GetProductCursor', $this->live);

                        echo " cursor updated successfully";
                    }
                    if ($debug == 3) {
                        dd($product);
                    }

                    DB::beginTransaction();
                    $node = $product->node;

                    $data = [
                        'stockId' => 0,
                        'handle' => $node->handle,
                        'productType' => $node->productType,
                        'vendor' => $node->vendor,
                        'productTags' => implode(', ', $node->tags),
                        'brand' => $node->vendor,
                        'title' => $node->title,
                        'descriptionHtml' => $node->descriptionHtml,
                        'isMatrix' => $node->hasOnlyDefaultVariant == true ? '0' : '1',
                        'status' => $node->status = 'active' ? 1 : 0,
                        'lastSyncDate' => now(),
                        'shopifyProductId' => $node->id,
                        'cursor' => $cursor
                    ];
                    if ($debug == 4) {
                        dd($data);
                    }
                    $result = $this->comSer->updateCreateProduct(
                        SourceProduct::class,
                        ['handle' => $node->handle],
                        $data
                    );
                    $this->variantsProcess($node->variants->edges, $result->id, $node->id);
                    DB::commit();

                    echo "product added successfully =>" . "$result->handle";
                    echo "<br>";
                }
            } else {
                print_r($response);
                echo "no products found";
            }
        } catch (Exception $e) {
            DB::rollBack();
            dd($e);
        }
    }
    public function variantsProcess($variants, $product_id, $pid)
    {
        foreach ($variants as $variant) {
            $node = $variant->node;
            $data = [
                'varinatId' => 0,
                'product_id' => $product_id,
                'sku' => $node->sku,
                'barcode' => $node->barcode,
                'compareAtPrice' => $node->compareAtPrice,
                'price' => $node->price,
                'color' => $this->getColorSize($node->selectedOptions, 'Color'),
                'size' => $this->getColorSize($node->selectedOptions, 'Size'),
                'inventoryQuantity' => $node->inventoryQuantity,
                'inventoryItemId' => $node->inventoryItem->id,
                'shopifyParentId' => $pid,
                'shopifyVariantId' => $node->id,
            ];
            $this->comSer->updateCreateProduct(
                SourceVariant::class,
                ['sku' => $node->sku],
                $data
            );
        }
    }

    public function getColorSize($node, $name)
    {
        foreach ($node as $option) {
            if ($option->name == $name) {
                return $option->value;
            }
        }
    }
}
