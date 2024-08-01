<?php

namespace Modules\Shopify\Http\Controllers\Middleware\Erply;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shopify\Services\ErplyService\ErplyProductService;
use Modules\Shopify\Services\SourceService\SourceProductGetService;
use Modules\Shopify\Models\ErplyModel\Product as ErplyModelProduct;

class ErplySohController extends Controller
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
            $products = $this->productService->getProducts(
                ['roadhouseSohStatus' => 1],
                $limit,
                $code
            );

            if ($debug == 1) {
                dd($products);
            }
            foreach ($products as $product) {
                DB::beginTransaction();
                echo "Product ID : " . $product->productID;
                $Variants =   $this->productService->getAllVariants(
                    $product->productID
                );
                echo "Variants Count : " . count($Variants);
                echo "<br>";
                if ($debug == 2) {
                    dd($Variants);
                }
                if (count($Variants) > 0) {

                    $this->manageVariants($Variants);
                } else {
                    $this->changeflag(2, $product->productID, 'no variants found');
                    echo "no variants found";
                }

                DB::commit();
            }
            echo "<br>";
            echo "done";
        } catch (Exception $e) {
            DB::rollBack();
            dd($e);
            return $e->getMessage();
        }
    }
    public function manageVariants($Variants)
    {
        $res = null;
        $source_product = [];
        foreach ($Variants as $Variant) {
            echo "Variants ID = " . $Variant->productID;
            echo "Variants code = " . $Variant->code;
            echo "<br>";
            $ShopifyProduict = $this->checkSourceProducts($Variant);


            if ($ShopifyProduict) {

                $source_product[] = $ShopifyProduict->id;
            }

            $variationSohs = $this->productService->getVariantSoh($Variant->productID);

            if (@$variationSohs && count($variationSohs) > 0) {
                echo "soh found =>" . count($variationSohs);
                $res =  $this->manageSoh($variationSohs, $Variant);

                if ($res == 4) {
                    $this->changeflag(4, $Variant->parentProductID, 'Missing varinats in Shopify');
                    continue;
                }

                if ($res == 5) {
                    $this->changeflag(4, $Variant->parentProductID, 'duplicate Varinats in source v');
                    continue;
                }

                if ($res == 1) {

                    $this->changeflag(0, $Variant->parentProductID, null);

                    echo "soh updated";
                    continue;
                } else {
                    dump($res);
                    $this->changeflag(6, $Variant->parentProductID, 'soh not updated');

                    echo "<br>";
                    echo "no source variant found";
                    echo "<br>";
                }
            } else {
                $this->changeflag(3, $Variant->parentProductID, 'no soh row found');

                echo "no soh found";
                return false;
            }
        }

        $source_product = array_unique($source_product);

        if (count($source_product) > 1) {
            $this->changeflag(9, $Variant->parentProductID, 'multiple source product found');
        }

        return $res;
    }

    public function manageSoh($variationSohs, $Variant)
    {
        $locations =  $this->productService->getLocations(['warehouseID']);
        $locations = $locations->select('warehouseID')->toArray();
        $erplsohLocation = $variationSohs->select('erplyWarehouseID')->toArray();
        //locations id whose soh is not created in erply
        $noSohLocation = $this->filterLocation($erplsohLocation, $locations);
        $countSourceProduct = [];

        $codes = [];
        if ($Variant->code) {
            $codes[] = $Variant->code;
        }

        if ($Variant->code2) {
            $codes[] = $Variant->code2;
        }

        if ($Variant->code3) {
            $codes[] = $Variant->code3;
        }

        print_r($codes);
        $sourceVarient = $this->sourceProductService->getSourceVariantsIN(
            'sku',
            $codes
        );


        if (count($sourceVarient) <= 0) {
            $this->changeflag(4, $Variant->parentProductID, 'no source variant found via sku');

            echo "no source variant  via sku";
            echo "<br>";
            $sourceVarient = $this->sourceProductService->getSourceVariantsIN(
                'barcode',
                $codes
            );
        }

        if (count($sourceVarient) <= 0) {
            echo "no source variant  via sku";
            echo "<br>";
            # $this->changeflag(4, $Variant->parentProductID, 'no barcode found in source v');

            return 4;
        }

        if (count($sourceVarient) > 1) {
            # $this->changeflag(4, $Variant->parentProductID, 'dublicate source variant found via sku');
            return 5;
        }
        $sourceVarient = $sourceVarient->first();


        $sourceProduct = $this->sourceProductService->getSourceProducts(['id' => $sourceVarient->product_id]);
        dump('source product', $sourceProduct);
        $flag = 0;
        foreach ($variationSohs as $variationSoh) {

            $locationId = $this->sourceProductService->getLocationsById($variationSoh->erplyWarehouseID);

            $sohdata =  [
                'varinatId' => $Variant->productID,
                'code' => $sourceVarient->sku,
                'currentStock' => $variationSoh->erplyCurrentStockValue,
                'pendingProcess' => 1,
                'lastStockUpdate' => date('Y-m-d H:i:s')
            ];

            $result =  $this->sourceProductService->insertSoh(
                $sourceProduct->id,
                $sourceVarient->id,
                $locationId->warehouseID,
                $sohdata
            );

            if ($result) {
                $flag = 1;
                echo "<br>";
                echo "Soh Inserted";
                echo "<br>";
                $this->sourceProductService->updateSourceProduct(
                    ['id' => $sourceProduct->id],
                    ['sohPendingProcess' => 1]
                );
            } else {
                $flag = 0;
                echo "<br>";
                echo "Soh Not Inserted";
                echo "<br>";
            }
        }

        //insert soh for no soh locations
        if (count($noSohLocation) > 0) {
            foreach ($noSohLocation as $noSoh) {

                $sohdata =  [
                    'varinatId' => $Variant->productID,
                    'code' => $sourceVarient->sku,
                    'currentStock' => 0,
                    'pendingProcess' => 1,
                    'lastStockUpdate' => date('Y-m-d H:i:s')
                ];

                $result =  $this->sourceProductService->insertSoh(
                    $sourceProduct->id,
                    $sourceVarient->id,
                    $noSoh['warehouseID'],
                    $sohdata
                );
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

        $this->sourceProductService->updateSourceProduct(
            ['id' => $sourceProduct->id],
            $sourceProductUpadte
        );


        return $flag;
    }

    public function changeflag($flag, $productID, $error = null)
    {
        return  ErplyModelProduct::where('productID', $productID)->update([
            'roadhouseSohStatus' => $flag,
            'error_Soh_item' => $error

        ]);
    }

    public function filterLocation($erplyWarehouseIDs, $warehouseLocations)
    {
        // Extract the erplyWarehouseID values into a simple array
        $erplyWarehouseIDsSimple = array_map(function ($item) {
            return $item['erplyWarehouseID'];
        }, $erplyWarehouseIDs);

        // Filter the warehouseLocations array to exclude matching warehouseIDs
        $filteredWarehouseLocations = array_filter($warehouseLocations, function ($location) use ($erplyWarehouseIDsSimple) {
            return !in_array($location['warehouseID'], $erplyWarehouseIDsSimple);
        });

        // Print the filtered data
        return $filteredWarehouseLocations;
    }

    public function checkSourceProducts($Variant)
    {

        $codes = [];
        if ($Variant->code) {
            $codes[] = $Variant->code;
        }

        if ($Variant->code2) {
            $codes[] = $Variant->code2;
        }

        if ($Variant->code3) {
            $codes[] = $Variant->code3;
        }

        $sourceVarient = $this->sourceProductService->getSourceVariantsIN(
            'sku',
            $codes
        );


        if (count($sourceVarient) <= 0) {
            $sourceVarient = $this->sourceProductService->getSourceVariantsIN(
                'barcode',
                $codes
            );
        }

        if (count($sourceVarient) <= 0) {
            return false;
        }

        if (count($sourceVarient) > 1) {
            return false;
        }
        $sourceVarient = $sourceVarient->first();


        $data =  $this->sourceProductService->getSourceProducts(['id' => $sourceVarient->product_id]);

        return $data;
    }
}
