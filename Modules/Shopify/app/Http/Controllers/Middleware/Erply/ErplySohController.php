<?php

namespace Modules\Shopify\Http\Controllers\Middleware\Erply;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
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
            if ($code) {
                $products = $this->productService->getProducts(['roadhouseSohStatus' => 1], 1, $code);
            } else {
                $products = $this->productService->getProducts(['roadhouseSohStatus' => 1], $limit, $code);
            }
            if ($debug == 1) {
                dd($products);
            }
            foreach ($products as $product) {

                echo "Product ID : " . $product->productID;
                $Variants =   $this->productService->getAllVariants($product->productID);
                echo "Variants Count : " . count($Variants);
                echo "<br>";
                if ($debug == 2) {
                    dd($Variants);
                }
                if ($Variants) {

                    $this->manageVariants($Variants);
                } else {
                    echo "no variants found";
                }
            }
            echo "done";
        } catch (Exception $e) {
            dd($e);
            return $e->getMessage();
        }
    }
    public function manageVariants($Variants)
    {

        foreach ($Variants as $Variant) {
            echo "Variants ID = " . $Variant->productID;
            echo "Variants code = " . $Variant->code;
            echo "<br>";
            $variationSohs = $this->productService->getVariantSoh($Variant->productID);

            if (@$variationSohs) {

                $res =  $this->manageSoh($variationSohs, $Variant);
                if ($res == 1) {

                    echo "soh updated";
                } else {
                    echo "no source variant found";
                }
            } else {
                echo "no soh found";
                return false;
            }
        }
    }

    public function manageSoh($variationSohs, $Variant)
    {
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
            return false;
        }

        if (count($sourceVarient) > 1) {
            ErplyModelProduct::where('productID', $Variant->productID)->update([
                'roadhouseSohStatus' => 4
            ]);
            return false;
        }
        $sourceVarient = $sourceVarient->first();

        $sourceProduct = $this->sourceProductService->getSourceProducts(['id' => $sourceVarient->product_id]);

        $flag = 0;
        foreach ($variationSohs as $variationSoh) {

            $locationId = $this->sourceProductService->getLocationsById($variationSoh->erplyWarehouseID);

            $sohdata =  [
                'varinatId' => $Variant->productID,
                'currentStock' => $variationSoh->erplyCurrentStockValue,
                'pendingProcess' => 1,
                'lastStockUpdate' => date('Y-m-d H:i:s')
            ];

            $result =  $this->sourceProductService->insertSoh(
                $sourceProduct->id,
                $sourceVarient->id,
                $locationId->id,
                $sohdata
            );

            if ($result) {
                $flag = 1;
                echo "<br>";
                echo "Soh Inserted";
                echo "<br>";
                $this->sourceProductService->updateSourceProduct(['id' => $sourceProduct->id], ['sohPendingProcess' => 1]);
            } else {
                $flag = 0;
                echo "<br>";
                echo "Soh Not Inserted";
                echo "<br>";
            }
        }

        return $flag;
    }
}
