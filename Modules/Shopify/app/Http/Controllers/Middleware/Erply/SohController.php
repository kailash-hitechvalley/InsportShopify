<?php

namespace Modules\Shopify\Http\Controllers\Middleware\Erply;

use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            $limit = $request->limit ?? 50;
            # get product details from erplay
            $products = $this->productService->getProducts(['roadhouseSohStatus' => 1], $limit, $code);


            foreach ($products as $product) {
                #get images details from the erply
                echo "Product ID : " . $product->productID;
                $Variants =   $this->productService->getAllVariants($product->productID);
                echo "Variants Count : " . count($Variants);
              

                echo "<br>";

                if ($Variants) {
                    $flag = 0;

                    foreach ($Variants as $Variant) {
                        echo "Variants = " . $Variant->code;
                        echo "<br>";
                        $sourceVarientId = null;

                        if ($Variant->productID) {
                            #get  variant details from erplay
                            $variationSohs = $this->productService->getVariantSoh($Variant->productID);

                            dump($variationSohs);
                            if ($variationSohs) {
                                foreach ($variationSohs as $variationSoh) {

                                    # get source variantion details
                                    $sourceVarient = $this->sourceProductService->getSourceVariants(['sku' => $Variant->code3]);
                                    #dump($sourceVarient);

                                    #get source product details from module
                                    $sourceProduct = $this->sourceProductService->getSourceProducts(['id' => $sourceVarient->product_id]);

                                    if ($sourceVarient) {

                                        $sourceVarientId = $sourceVarient->id;
                                    } else {
                                        ErplyModelProduct::where('productID', $product->productID)->update([
                                            'roadhouseStatus' => 1
                                        ]);
                                        $this->sourceProductService->updateSourceProduct(['id' => $sourceProduct->id], ['sohPendingProcess' => 0]);
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
                                        $sourceProduct->id,
                                        $locationId->id,
                                        $sohdata
                                    );


                                    if ($result) {
                                        $this->sourceProductService->updateSourceProduct(['id' => $sourceProduct->id], ['sohPendingProcess' => 1]);

                                        echo "<br>";
                                        echo "Total Stock :" . $result->currentStock;
                                        echo "Soh Inserted Successfully";
                                        echo "<br>";
                                        $flag = 1;
                                    } else {
                                        echo "<br>";
                                        echo "Soh Not Inserted";
                                        echo "<br>";
                                    }
                                }
                            } else {
                                echo
                                "soh noty found";
                            }
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

                    $this->sourceProductService->updateSourceProduct(['id' => $sourceProduct->id], $sourceProductUpadte);
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
            $this->productService->updateProducts($product->productID, ['roadhouseStatus' => 1]);

            echo "Something went wrong";
            #info($e->getMessage());
            return $e->getMessage();
        }
    }
}
