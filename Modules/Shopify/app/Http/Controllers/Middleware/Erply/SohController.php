<?php

namespace Modules\Shopify\Http\Controllers\Middleware\Erply;

use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Shopify\Models\ErplyModel\Product as ErplyModelProduct;
use Modules\Shopify\Services\ErplyService\ErplyProductService;
use Modules\Shopify\Services\SourceService\SourceProductGetService;

class SohController extends Controller
{

    protected $productService;
    protected $sourceProductService;
    public function __construct(ErplyProductService $productService)
    {
        $this->productService = $productService;
        $this->sourceProductService = new SourceProductGetService();
    }
    public function index(Request $request)
    {
        try {
            $code = $request->code ?? '';
            $limit = $request->limit ?? 20;
            $debug = $request->debug ?? 0;
            # get product details from erplay
            $products = $this->productService->getProducts(['roadhouseSohStatus' => 1], $limit, $code);
            if ($debug == 1) {
                dd($products);
            }

            foreach ($products as $product) {
                #get images details from the erply
                echo "Product ID : " . $product->productID;
                $Variants =   $this->productService->getAllVariants($product->productID);
                echo "Variants Count : " . count($Variants);


                echo "<br>";
                if ($debug == 2) {
                    dd($Variants);
                    # code...
                }
                if ($Variants) {
                    $flag = 0;

                    foreach ($Variants as $Variant) {
                        echo "Variants ID = " . $Variant->productID;
                        echo "Variants code = " . $Variant->code;
                        echo "<br>";

                        echo "Variants code3 = " . $Variant->code3;

                        echo "<br>";
                        $sourceVarientId = null;

                        if ($Variant->productID) {
                            echo "Product ID = " . $Variant->productID;
                            #get  variant details from erplay
                            $variationSohs = $this->productService->getVariantSoh($Variant->productID);
                            if ($debug == 3) {
                                dd($variationSohs);
                                # code...
                            }
                            #dump($variationSohs);
                            if (count($variationSohs) > 0 && $variationSohs) {
                                foreach ($variationSohs as $variationSoh) {

                                    # get source variantion details
                                    $sourceVarient = $this->sourceProductService->getSourceVariantsIN(
                                        'sku',
                                        [$Variant->code, $Variant->code3, $Variant->code2]
                                    );

                                    if (!$sourceVarient) {
                                        $sourceVarient = $this->sourceProductService->getSourceVariantsIN(
                                            'barcode',
                                            [$Variant->code, $Variant->code3, $Variant->code2]
                                        );
                                    }
                                    if (!$sourceVarient) {
                                        ErplyModelProduct::where('productID', $product->productID)->update([
                                            'roadhouseSohStatus' => 5
                                        ]);
                                        continue;
                                    }
                                    if (count($sourceVarient) > 1) {
                                        ErplyModelProduct::where('productID', $product->productID)->update([
                                            'roadhouseSohStatus' => 4
                                        ]);
                                        continue;
                                    }
                                    $sourceVarient = $sourceVarient->first();
                                    #  dd($sourceVarient);
                                    #get source product details from module
                                    $sourceProduct = $this->sourceProductService->getSourceProducts(['id' => $sourceVarient->product_id]);
                                  #  dump($sourceProduct);
                                    if ($sourceVarient && $sourceProduct) {

                                        $sourceVarientId = $sourceVarient->id;
                                    } else {
                                        echo "source product not found" . "<br>";
                                        ErplyModelProduct::where('productID', $product->productID)->update([
                                            'roadhouseStatus' => 1,
                                            'roadhouseSohStatus' => 2
                                        ]);
                                        $this->sourceProductService->updateSourceProduct(['id' => $sourceProduct->id], ['sohPendingProcess' => 0, 'stockId' => $product->productID]);
                                        continue;
                                    }

                                    $locationId = $this->sourceProductService->getLocationsById($variationSoh->erplyWarehouseID);

                                    $sohdata =  [
                                        'product_id' => $sourceProduct->id,

                                        'varinatId' => $Variant->productID,

                                        'currentStock' => $variationSoh->erplyCurrentStockValue,
                                        'pendingProcess' => 1,
                                        'lastStockUpdate' => date('Y-m-d H:i:s')
                                    ];

                                    $result =  $this->sourceProductService->insertSoh(
                                        $sourceVarientId,
                                        $locationId->id,
                                        $sohdata
                                    );


                                    if ($result) {
                                        $this->sourceProductService->updateSourceProduct(['id' => $sourceProduct->id], ['sohPendingProcess' => 1, 'stockId' => $product->productID]);

                                        echo "<br>";
                                        echo "Total Stock :" . $result->currentStock;
                                        echo "Soh Inserted Successfully";
                                        echo "<br>";
                                        $flag = 1;
                                    } else {
                                        ErplyModelProduct::where('productID', $product->productID)->update(['roadhouseSohStatus' => 3]);

                                        continue;
                                        echo "<br>";
                                        echo "Soh Not Inserted";
                                        echo "<br>";
                                    }
                                }
                            } else {

                                echo "soh not found";
                            }
                        } else {

                            echo "Variants Product Id Not Found";
                        }
                    }
                    if ($flag == 1) {
                        $sourceProductUpadte = [
                            'sohPendingProcess' => 1,
                            'lastSyncDate' => date('Y-m-d H:i:s')
                        ];
                    } else {
                        $sourceProductUpadte = [
                            'sohPendingProcess' => 0,
                            'lastSyncDate' => date('Y-m-d H:i:s')
                        ];
                    }
                    print_r($sourceProductUpadte);
                    if (@$sourceProduct) {
                        echo "Product Found in Source Module";
                        $this->sourceProductService->updateSourceProduct(['id' => $sourceProduct->id], $sourceProductUpadte);
                    }
                    $updateData = [
                        'roadhouseSohStatus' => 0

                    ];
                } else {
                    echo "Product Not Found in Source Module or soh not found";
                    $updateData = [
                        'roadhouseSohStatus' => 3

                    ];
                }


                $this->productService->updateProducts($product->productID, $updateData);

                echo "Procerss Completed for Product ID : " . $product->productID;
            }
            echo "Whole Process Completed";
        } catch (Exception $e) {
            dd($e);
            $this->productService->updateProducts($product->productID, ['roadhouseStatus' => 1, 'roadhouseSohStatus' => 2]);

            echo "Something went wrong";
            #info($e->getMessage());
            return $e->getMessage();
        }
    }
}
