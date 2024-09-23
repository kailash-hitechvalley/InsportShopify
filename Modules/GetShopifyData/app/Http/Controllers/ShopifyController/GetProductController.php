<?php

namespace Modules\GetShopifyData\Http\Controllers\ShopifyController;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\GetShopifyData\Services\CommonService;
use Modules\GetShopifyData\Services\ShopifyGetService;
use Modules\Shopify\Models\ErplyModel\Product;
use Modules\Shopify\Models\Source\SourceLocation;
use Modules\Shopify\Models\Source\SourceProduct;
use Modules\Shopify\Models\Source\SourceVariant;
use Modules\Shopify\Traits\ShopifyTrait;

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
            $dev = $request->get('dev') ?? 0;

            if ($dev == 0) {
                //dev mode
                die('dev mode enabled');
            }

            $id = $request->get('id') ?? null;
            $debug = $request->get('debug') ?? 0;
            $limit = $request->get('limit') ?? 20;
            $cursorName = $request->get('cursorName') ?? 'GetProductCursor'; //GetProductUpdatedBYCursor

            $response = $this->service->getShopifyProducts($id, $limit, $cursorName, $debug);

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
                        if ($cursorName == 'GetProductUpdatedBYCursor') {
                            $cursor = $product->node->updatedAt;
                        }
                        $this->comSer->saveCursor($cursor, $cursorName, $this->live);

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
                        ['shopifyProductId' => $node->id],
                        $data
                    );
                    if ($result->sohPendingProcess == 8 && $result->shopifyIssueTags != null) {
                        echo "checkIssueTags on product tags";
                        $this->checkIssueTags($node);
                    }
                    #  $this->variantsProcess($node->variants->edges, $result->id, $node->id);
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
                'shopifyPendingProcess' => 1,
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
                ['shopifyVariantId' => $node->id],
                $data
            );
        }
    }
    public function getProductVariants(Request $request)
    {

        $debug = $request->get('debug') ?? 0;
        $limit = $request->get('limit') ?? 3;
        $cursorName = $request->get('cursor') ?? 'GetProductVariantsCursor'; //getVarinatsViaDate

        $response = $this->service->getShopifyVariants($limit, $debug, $cursorName);

        if ($debug == 2) {
            dd($response);
        }
        try {
            if ($response->data->productVariants->edges) {
                $variants = $response->data->productVariants->edges;
                if ($debug == 3) {
                    dd($variants);
                }

                $lastKey = array_key_last($variants);

                foreach ($variants as $key => $varinat) {
                    DB::beginTransaction();
                    $cursor = $varinat->node->updatedAt;
                    if ($cursorName == 'GetProductVariantsCursor') {
                        $cursor = $varinat->cursor;
                    }
                    if ($key === $lastKey) {
                        $this->comSer->saveCursor($cursor, $cursorName, $this->live);

                        # echo " cursor updated successfully";
                    }
                    if ($debug == 3) {
                        dd($varinat);
                    }

                    $product = SourceProduct::where('shopifyProductId', $varinat->node->product->id)->first();

                    $this->singleVarinatsProcess($varinat->node, @$product->id);
                    DB::commit();
                }
                return response()->json([
                    'status' => true,
                    'message' => 'Product Variants added successfully',
                    'data' => $response
                ]);
            }
        } catch (Exception $th) {
            DB::rollBack();
            dd($th);
        }
    }
    public function singleVarinatsProcess($node, $product_id = null)
    {
        $data = [
            'varinatId' => 0,
            'product_id' => $product_id,
            'sku' => $node->sku,
            'barcode' => $node->barcode,
            'compareAtPrice' => $node->compareAtPrice,
            'shopifyPendingProcess' => 1,
            'price' => $node->price,
            'color' => $this->getColorSize($node->selectedOptions, 'Color'),
            'size' => $this->getColorSize($node->selectedOptions, 'Size'),
            'inventoryQuantity' => $node->inventoryQuantity,
            'inventoryItemId' => $node->inventoryItem->id,
            'shopifyParentId' => $node->product->id,
            'shopifyVariantId' => $node->id,
        ];
        $this->comSer->updateCreateProduct(
            SourceVariant::class,
            ['shopifyVariantId' => $node->id],
            $data
        );
    }

    public function getColorSize($node, $name)
    {
        foreach ($node as $option) {
            if ($option->name == $name) {
                return $option->value;
            }
        }
    }

    public function getLocations(Request $request)
    {
        $debug = $request->get('debug') ?? 0;
        $response  =   $this->service->getShopifyLocations();

        if ($debug == 1) {
            dd($response);
        }

        $locations = $response->data->locations->edges;
        if ($debug == 2) {
            dd($locations);
        }

        foreach ($locations as $location) {
            $data = [
                'shopifyLocationId' => $location->node->id,
            ];
            SourceLocation::where('name', $location->node->name)->update($data);
        }

        echo "Locations updated successfully";
    }

    public function getProductVariantsNull(Request $request)
    {
        $variants = SourceVariant::whereNull('product_id')->get();
        foreach ($variants as $variant) {
            $product = SourceProduct::where('shopifyProductId', $variant->shopifyParentId)->first();
            $variant->product_id = $product->id;
            $variant->save();
        }

        echo "Variants updated successfully";
    }

    public function checkIssueTags($product)
    {
        echo "Checking issue tags on product tags...\n";
        $issues = ['ErplyMultipleParent', 'ErplyVariantNotFound', 'MultipleErplyVariantFound'];
        $issueFound = false;

        foreach ($issues as $issue) {
            if (in_array($issue, $product->tags)) {
                echo "Issue is still pending: " . $issue . "\n";
                $issueFound = true;
                break; // If you want to stop checking once one issue is found
            }
        }

        if (!$issueFound) {
            SourceProduct::where('shopifyProductId', $product->id)->update(['sohPendingProcess' => 9]);
            echo "No issues found, updated sohPendingProcess to 9.\n";
        }
    }
}
