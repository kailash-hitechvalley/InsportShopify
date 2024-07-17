<?php

namespace Modules\Shopify\Services\ErplyService;

use Exception;
use Modules\Shopify\Models\ErplyModel\BrandDiscountPrice;
use Modules\Shopify\Models\ErplyModel\Category;
use Modules\Shopify\Models\ErplyModel\ErplySoh;
use Modules\Shopify\Models\ErplyModel\Images;
use Modules\Shopify\Models\ErplyModel\Order;
use Modules\Shopify\Models\ErplyModel\PriceList;
use Modules\Shopify\Models\ErplyModel\Product;
use Modules\Shopify\Models\ErplyModel\SpecialPriceList;
use Modules\Shopify\Models\ErplyModel\Variant;
use Modules\Shopify\Models\ErplyModel\WareHouseLocation;

class ErplyProductService
{
    public function getProducts($whereCondition, $limit, $code)
    {
        try {
            $products = Product::with(['variants' => function ($query) {
                $query->orderBy('colorOrder')->orderBy('sizeOrder');
            }])->when($code != '', function ($query) use ($code) {
                $query->where('code', $code);
            })->when($code == '', function ($query) use ($whereCondition) {
                $query->where($whereCondition);
            })->orderBy('lastModified', 'desc')->limit($limit)->get();

            return $products;
        } catch (Exception $th) {
            return $th->getMessage();
        }
    }
    public function getImagesProducts($whereCondition, $limit)
    {
        $products = Product::with(['images' => function ($query) {
            $query->orderBy('order', 'asc');
        }])->where($whereCondition)->limit($limit)->get();

        return $products;
    }
    public  function updateProducts($id, $datas)
    {
        return Product::where('productID', $id)->update($datas);
    }

    public function getCategory()
    {
        return Category::where([
            'roadhouseStatus' => 1
        ])->take(100)->get();
    }
    public  function updateCategory($id, $datas)
    {
        return Category::where('id', $id)->limit(2)->update($datas);
    }

    public function getLocations()
    {
        return WareHouseLocation::all();
    }


    public function getImages($productID)
    {
        return Images::query()
            ->selectRaw('ANY_VALUE(id), colourID')
            ->where('parentProductID', $productID)
            ->groupBy('colourID')
            ->get();
    }



    public function getVariants($productID)
    {
        return Variant::where('productID', $productID)->first();
    }

    public function getAllVariants($productID)
    {
        return Variant::where('parentProductID', $productID)->get();
    }

    public function getSoh($whereCondition)
    {
        return ErplySoh::where($whereCondition)->get();
    }
    public function getVariantSoh($productID)
    {
        return ErplySoh::where('erplyProductID', $productID)->get();
    }

    public function getPriceLists($pricelistID)
    {
        return PriceList::where([
            'pricelistID' => $pricelistID,
            'active' => 1,
            'isDeleted' => 0
        ])->first();
    }
    public function checkAssignedToWareHouse($pricelistID)
    { // check all column of pricelists
        return WareHouseLocation::where('warehouseID', 8)
            ->where(function ($q) use ($pricelistID) {
                $q->where('priceListID', $pricelistID)
                    ->orWhere('priceListID2', $pricelistID)
                    ->orWhere('priceListID3', $pricelistID)
                    ->orWhere('priceListID4', $pricelistID)
                    ->orWhere('priceListID5', $pricelistID);
            })
            ->first();
    }
    public function getSpecialPriceList($productID)
    {
        return SpecialPriceList::where([
            'productID' => $productID,
            'isDeleted' => 0
        ])->get();
    }
    public function getBrandDiscount($brandID)
    {
        return BrandDiscountPrice::where([
            'brandID' => $brandID,
            'isDeleted' => 0
        ])->where('discount', '>', 0)->first();
    }

    public function getOrdersDetails($orderby, $order)
    {
        return Order::orderBy($order, $orderby)->get();
    }
}
