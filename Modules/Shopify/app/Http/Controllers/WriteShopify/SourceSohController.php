<?php

namespace Modules\Shopify\Http\Controllers\WriteShopify;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Shopify\Models\ErplyModel\Product;
use Modules\Shopify\Models\ErplyModel\Variant;
use Modules\Shopify\Models\Source\SourceProduct;
use Modules\Shopify\Models\Source\SourceVariant;
use Modules\Shopify\Services\SourceProductService;
use Modules\Shopify\Traits\ShopifyTrait;
use Modules\Shopify\Traits\ShopifyProductMutationTrait;


class SourceSohController extends Controller
{

    use ShopifyTrait, ShopifyProductMutationTrait;


    protected $productService;

    public function __construct(SourceProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $code = $request->input('code', '');
        $debug = $request->input('debug', 0);
        try {

            if ($code) {

                $products = $this->productService->getSohPendingProduct(
                    ['handle' => $code],
                    1
                );
            } else {

                $products = $this->productService->getSohPendingProduct(
                    [
                        'sohPendingProcess' => 1,
                        'shopifyPendingProcess' => 0
                    ],
                    1
                );
            }
            if ($debug == 1) {
                dd($products);
            }
            if (count($products) <= 0) {
                echo 'no products found';

                // set soh pending process to 1 for all products with sohPendingProcess = 2
                SourceProduct::where('sohPendingProcess', 2)->update(['sohPendingProcess' => 1]);
                exit;
            }

            foreach ($products as $product) {
                echo "Product Title : " . $product->title . "<br>";
                echo "Product Handle : " . $product->handle . "<br>";

                # $productExists = $this->productExistsByShopifyId($product->shopifyProductId);
                // if ($product->shopifyProductId) {
                //     if ($productExists['exist'] != 1) {
                //         $productExists = $this->productExists($product->handle);
                //     }
                // } else {
                //     $productExists = $this->productExists($product->handle);
                // }
                // if ($productExists['exist'] != 1) {
                //     $this->productService->updateProduct($product->id, [
                //         'sohPendingProcess' => 2,
                //         'lastPushedDate' => date('Y-m-d H:i:s'),
                //         'errorMessage' => 'product not found'
                //     ]);
                //     continue;
                // }
                # $shopifyProductId = $productExists['handleID'];
                $shopifyProductId = $product->shopifyProductId;
                echo "Shopify Product Id : " . $shopifyProductId . "<br>";
                $variants = $product->variants;
                $totalSoh = 0;
                $flag = 0;
                if (count($variants) <= 0) {
                    $this->productService->updateProduct(
                        $product->id,
                        [

                            'sohPendingProcess' => 3,
                            'shopifyPendingProcess' => 1,
                            'lastPushedDate' => date('Y-m-d H:i:s'),
                            'errorMessage' => 'no active variants found'
                        ]
                    );
                    // $statusMutation =  $this->changeStatusMutation(
                    //     $shopifyProductId,
                    //     'ARCHIVED'
                    // );
                    // $statusResponse = $this->sendShopifyQueryRequestV2(
                    //     'POST',
                    //     $statusMutation,
                    //     $this->live
                    // );
                    continue;
                }
                $check = $this->checkErplyParentVariant($variants, $product->id);
                if ($check) {
                    continue;
                }

                foreach ($variants as $variant) {
                    echo 'variant id = ' . $variant->shopifyVariantId . '<br>';
                    echo "sku = " . $variant->sku . " soh = " . $variant->sourceSoh()->sum('currentStock') . "<br>";
                    $sourceSohs = $variant->sourceSoh()->get();

                    if (count($sourceSohs) <= 0) {
                        $this->productService->updateProduct(
                            $product->id,
                            [
                                'sohPendingProcess' => 4,
                                'lastPushedDate' => date('Y-m-d H:i:s'),
                                'errorMessage' => 'no soh found'
                            ]
                        );

                        continue;
                    }

                    $mutations =  $this->updateProductSohMutation(
                        $sourceSohs,
                        $variant->inventoryItemId,
                        $variant->shopifyVariantId,
                        $variant->id
                    );


                    if (isset($mutations['status']) && (int)$mutations['status'] == 0 && $mutations['error']) {
                        dump("have some error");
                        $this->productService->updateProduct(
                            $product->id,
                            [
                                'sohPendingProcess' => 2,
                                'lastPushedDate' => date('Y-m-d H:i:s'),
                                'errorMessage' => $mutations['error']
                            ]
                        );
                        exit;
                    }


                    if (isset($mutations['status']) && (int)$mutations['status'] == 1 && $mutations['locationActivated'] == 1) {
                        dump("location activated");

                        $mutations =  $this->updateProductSohMutation(
                            $sourceSohs,
                            $variant->inventoryItemId,
                            $variant->shopifyVariantId,
                            $variant->id
                        );


                        if (isset($mutations['status']) && (int)$mutations['status'] == 0 && $mutations['error']) {

                            $this->productService->updateProduct(
                                $product->id,
                                [
                                    'sohPendingProcess' => 2,
                                    'lastPushedDate' => date('Y-m-d H:i:s'),
                                    'errorMessage' => $mutations['error']
                                ]
                            );
                            exit;
                        }
                    }

                    dump('no error');
                    $totalSoh += $mutations['sumOfSoh'] ?? 0;
                    if ($debug == 2) {
                        dd($mutations);
                    }
                    if ($mutations['status'] == 2) {
                        $response = $this->sendShopifyQueryRequestV2(
                            'POST',
                            $mutations['mutation'],
                            $this->live
                        );
                        echo "<pre>";
                        print_r($response);
                        echo "</pre>";

                        if ($debug == 3) {
                            dd($response);
                        }

                        if (!empty($response->data->updateInventoryItem->userErrors)) {
                            echo "have some error";
                            $flag = 0;
                        } else {
                            $flag = 1;
                        }
                    }
                }


                if ($flag == 1) {
                    echo "product Soh = " . $totalSoh . " for product " . $product->id . "<br>";
                    $updateData = [
                        'sohPendingProcess' => 0,
                        'lastPushedDate' => date('Y-m-d H:i:s')
                    ];
                } else {
                    $updateData = [
                        'sohPendingProcess' => 2,
                        'lastPushedDate' => date('Y-m-d H:i:s'),
                        'errorMessage' => "soh failed to update"
                    ];
                }


                $this->productService->updateProduct($product->id, $updateData);
                echo "Total Soh = " . $totalSoh . " for product " . $product->id . "<br>";



                // if ($totalSoh <= 0) {
                //     echo "product Soh = " . $totalSoh . " for product " . $product->id;

                //     $statusMutation =  $this->changeStatusMutation($shopifyProductId, 'ARCHIVED');

                //     echo "Product " . $product->id . " archived successfully";
                // } else {
                //     $statusMutation = $this->changeStatusMutation($shopifyProductId, 'ACTIVE');
                // }

                // $newProductStatus = $totalSoh <= 0
                //     ? 'ARCHIVED' : ($totalSoh > 0 && $product->status == 1
                //         ? 'ACTIVE' : 'ARCHIVED');

                // $statusMutation = $this->changeStatusMutation(
                //     $shopifyProductId,
                //     $newProductStatus
                // );

                // $statusResponse = $this->sendShopifyQueryRequestV2(
                //     'POST',
                //     $statusMutation,
                //     $this->live
                // );

                // print_r($statusResponse);
            }
            echo "Process Completed  ";
        } catch (Exception $e) {

            dd($e->getMessage());

            return $e->getMessage();

            //throw $th;
        }
    }

    public function getErplyParentVariant($sku)
    {
        return Variant::where('code', $sku)
            ->orWhere('code2', $sku)
            ->orWhere('code3', $sku)
            ->get();
    }

    public function checkErplyParentVariant($variants, $productid)
    {

        $parent = [];
        $flag = 0;
        foreach ($variants as $variant) {
            $ErplyParent = $this->getErplyParentVariant($variant->sku);

            if (count($ErplyParent) > 1 || count($ErplyParent) == 0) {
                SourceVariant::where('id', $variant->id)->update([
                    'sohPendingProcess' => 8,
                    'error_variants' => count($ErplyParent) == 0 ? 'No  Variants Found on Erply' : 'Multiple Parent  Found on Erply',
                ]);

                $flag = 1;
            } else {
                $parent[] = $ErplyParent->first()->parentProductID;
            }
        }
        if ($flag == 1) {
            $error = count($ErplyParent) == 0 ? 'No  Variants Found on Erply' : 'Multiple Parent  Found on Erply';
            echo $error;
            return  $this->productService->updateProduct($productid, [
                'sohPendingProcess' => 8,
                'lastPushedDate' => date('Y-m-d H:i:s'),
                'errorMessage' => $error
            ]);
        }
        if (count(array_unique($parent)) > 1) {
            echo "multiple parent variants found=>" . implode(',', array_unique($parent));
            return  $this->productService->updateProduct($productid, [
                'sohPendingProcess' => 9,
                'lastPushedDate' => date('Y-m-d H:i:s'),
                'errorMessage' => "multiple parent variants found=>" . implode(',', array_unique($parent))
            ]);
        }
        return false;
    }
}
