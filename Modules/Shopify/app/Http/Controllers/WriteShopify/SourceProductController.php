<?php

namespace Modules\Shopify\Http\Controllers\WriteShopify;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Shopify\Models\Source\SourceProduct;
use Modules\Shopify\Services\SourceProductService;
use Modules\Shopify\Traits\ShopifyProductMutationTrait;
use Modules\Shopify\Traits\ShopifyTrait;

class SourceProductController extends Controller
{
    use ShopifyTrait, ShopifyProductMutationTrait;
    /**
     * Display a listing of the resource.
     */
    protected $live = 1;
    protected $productService;
    public function __construct(SourceProductService $productService)
    {
        $this->productService = $productService;
    }
    public function index(Request $request)
    {
        $debug = $request->input('debug', 0);
        $code = $request->input('code', '');
        // $isManual = $request->input('isManual', 0);
        // if ($isManual == 0) {
        //     dd("is manual");
        // }

        try {

            if ($code) {

                $products = $this->productService->getProduct($code);
            } else {

                $products = $this->productService->getAllProduct(3);
            }
            if ($debug == 1) {
                dd($products);
            }
            if (count($products) <= 0) {
                echo 'no products found';
                SourceProduct::where('shopifyPendingProcess', 2)->update(['shopifyPendingProcess' => 1]);
                exit;
            }

            foreach ($products as $product) {
                $this->productService->updateProduct($product->id, [

                    'lastPushedDate' => date('Y-m-d H:i:s')
                ]);
                echo "Product Title : " . $product->title . "<br>";
                echo "Product Handle : " . $product->handle . "<br>";

                $mutations =   $this->createOrUpdateProductMutation($product);
                if ($mutations == 1) {
                    $this->productService->updateProduct($product->id, [
                        'shopifyPendingProcess' => 2,
                        'lastPushedDate' => date('Y-m-d H:i:s')
                    ]);

                    continue;
                }
                if ($debug == 2) {
                    dd($mutations);
                }

                $response = $this->sendShopifyQueryRequestV2('POST', $mutations, $this->live);
                print_r($response);
                if ($debug == 3) {
                    dd($response);
                }
                $errors = $response->data->productCreate->userErrors[0]->message ??
                    $response->data->productUpdate->userErrors[0]->message ?? null;
                if (
                    $errors
                ) {
                    $this->productService->updateProduct($product->id, [
                        'shopifyPendingProcess' => 3,
                        'lastPushedDate' => date('Y-m-d H:i:s'),
                        'errorMessage' => json_encode($errors),
                    ]);
                    continue;
                }


                # check for error in creating

                if (isset($response->data->productCreate) || isset($response->data->productUpdate)) {

                    # get product id
                    $shopifyProductId = $response->data->productCreate->product->id
                        ?? $response->data->productUpdate->product->id;
                    $this->linkChannel($shopifyProductId);

                    echo "product id " . $shopifyProductId;
                    echo "<br>";
                    echo "product create or updated";

                    # get productvariants
                    $variants =  $response->data->productCreate->product->variants->edges
                        ?? $response->data->productUpdate->product->variants->edges ?? [];


                    foreach ($variants as $variant) {

                        $variantId = $variant->node->id;

                        $sku = $variant->node->sku;


                        $updateVariants = [
                            'shopifyVariantId' => $variantId,
                            'inventoryItemId' => $variant->node->inventoryItem->id,
                        ];

                        $this->productService->updateVariants($sku, $updateVariants);
                    }
                    # update productdata
                    $updateData = [
                        'shopifyPendingProcess' => 0, # success
                        'pricePendingProcess' => 0,
                        'lastPushedDate' => date('Y-m-d H:i:s'),
                        'shopifyProductId' => $shopifyProductId
                    ];
                } else {
                    $updateData = [
                        'shopifyPendingProcess' => 3, # success
                        'lastPushedDate' => date('Y-m-d H:i:s'),

                    ];
                }

                $this->productService->updateProduct($product->id, $updateData);
            }


            echo "Process Completed";
        } catch (Exception  $th) {
            info($th->getMessage());

            return $th->getMessage();
            //throw $th;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
