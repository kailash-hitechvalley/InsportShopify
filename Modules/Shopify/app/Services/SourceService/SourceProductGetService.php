<?php

namespace Modules\Shopify\App\Services\SourceService;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Shopify\App\Models\ErplyModel\Category;
use Modules\Shopify\App\Models\ErplyModel\CategoryAttribute;
use Modules\Shopify\App\Models\Source\SourceCategory;
use Modules\Shopify\App\Models\Source\SourceImage;
use Modules\Shopify\App\Models\Source\SourceLocation;
use Modules\Shopify\App\Models\Source\SourceProduct;
use Modules\Shopify\App\Models\Source\SourceSoh;
use Modules\Shopify\App\Models\Source\SourceVariant;

class SourceProductGetService
{
    public function insertProducts($productData)
    {
        try {
            $status = 0;
            if ($productData->active == 1 && $productData->displayedInWebshop == 1 && $productData->erplyDeleted == 0) {
                $status = 1;
            }
            $type = '0';
            if ($productData->type == 'MATRIX') {
                $type = '1';
            }
            $tags = $this->getTags($productData);

            $payload =  [
                'stockId' => $productData->productID,
                'category_id' => $productData->categoryID,
                'handle' => $productData->code,
                'barcode' => $productData->code2,
                // 'productType' => $productData->brandName,
                'vendor' => $productData->brandName,
                'productTags' => $tags,
                'brand' => $productData->brandName,
                'title' => $productData->description,
                'descriptionHtml' => $productData->longdesc,
                'isMatrix' => $type,
                'status' => $status,
                // 'mainImage' =>$productData->,
                // 'addGst' =>$productData->,
                'shopifyPendingProcess' => 1,
                // 'sohPendingProcess' => 1,
                // 'pricePendingProcess' => 1,
                // 'imagePendingProcess' => 1,
                'lastSyncDate' => date('Y-m-d H:i:s'),
                'sourceAddedDate' => $productData->added,
                'sourceUpdatedDate' => $productData->lastModified


            ];
            #  dd($payload, $productData);
            return  SourceProduct::updateOrCreate([
                'stockId' => $productData->productID
                // 'handle' => $productData->code
            ], $payload);
        } catch (Exception  $th) {
            dd($th);
            return $th->getMessage();
        }
    }

    public function getSourceProducts($condition)
    {
        return SourceProduct::where($condition)->first();
    }
    public function getSourceVariants($condition)
    {

        return SourceVariant::where($condition)->first();
    }
    public function updateSourceProduct($condition, $datas)
    {

        return SourceProduct::where($condition)->update($datas);
    }
    public function updateSourceVariants($condition, $datas)
    {

        return SourceVariant::where($condition)->update($datas);
    }
    public function getTags($productData)
    {
        try {

            $tags = [];

            $tags[] = '"' . $productData->code . '"';
            $tags[] = '"' . $productData->supplierName . '"';
            $tags[] = '"' . $productData->brandName . '"';
            $tags[] = '"' . $productData->groupName . '"';

            $categoryAttributes = CategoryAttribute::where('erplyID', $productData->productID)->first();
            $categoryIds = explode(',', $categoryAttributes->category);

            $categoriesNew = DB::connection('mysql_source')->table('newsystem_product_categories AS c1')
                ->select(
                    'c1.productCategoryName AS category_name_1',
                    'c2.productCategoryName AS category_name_2',
                    'c3.productCategoryName AS category_name_3',
                    'c4.productCategoryName AS category_name_4',
                    // 'c1.shopifyCollectionID AS collection_id_1',
                    // 'c2.shopifyCollectionID AS collection_id_2',
                    // 'c3.shopifyCollectionID AS collection_id_3',
                    // 'c4.shopifyCollectionID AS collection_id_4'
                )
                ->leftJoin('newsystem_product_categories AS c2', 'c1.parentCategoryID', '=', 'c2.productCategoryID')
                ->leftJoin('newsystem_product_categories AS c3', 'c2.parentCategoryID', '=', 'c3.productCategoryID')
                ->leftJoin('newsystem_product_categories AS c4', 'c3.parentCategoryID', '=', 'c4.productCategoryID')
                ->whereIn('c1.productCategoryID', $categoryIds)
                ->get();
            # dd($categoriesNew);

            foreach ($categoriesNew as $categoryNew) {

                if ($categoryNew->category_name_4 != '') {
                    $tags[] = '"' . $categoryNew->category_name_4 . '"';
                }
                if ($categoryNew->category_name_3 != '') {
                    if ($categoryNew->category_name_4 != '') {
                        $tags[] =  '"' . $categoryNew->category_name_4  . '-' . $categoryNew->category_name_3 . '"';
                    } else {
                        $tags[] = '"' . $categoryNew->category_name_4 . '"';
                    }
                }
                if ($categoryNew->category_name_3 != ''  && $categoryNew->category_name_2 != '') {
                    if ($categoryNew->category_name_4 != '') {
                        $tags[] = '"' . $categoryNew->category_name_4  . '-' . $categoryNew->category_name_3 . '-' . $categoryNew->category_name_2 . '"';
                    } else {
                        $tags[] = '"' . $categoryNew->category_name_3 . '-' . $categoryNew->category_name_2 . '"';
                    }
                }
                if ($categoryNew->category_name_3 != ''  && $categoryNew->category_name_2 != ''  && $categoryNew->category_name_1 != '') {
                    if ($categoryNew->category_name_4 != '') {
                        $tags[] = '"' . $categoryNew->category_name_4  . '-' . $categoryNew->category_name_3 . '-' . $categoryNew->category_name_2 . '-' . $categoryNew->category_name_1 . '"';
                    } else {

                        $tags[] = '"' . $categoryNew->category_name_3 . '-' . $categoryNew->category_name_2 . '-' . $categoryNew->category_name_1 . '"';
                    }
                }

                // $catCollectionIds[] =  $categoryNew['collection_id_1'];
                // $catCollectionIds[] =  $categoryNew['collection_id_2'];
                // $catCollectionIds[] =  $categoryNew['collection_id_3'];
                // $catCollectionIds[] =  $categoryNew['collection_id_4'];
            }
            $tags = array_unique($tags);

            return implode(',', $tags);
        } catch (Exception $th) {
            return $th->getMessage();
        }
    }
    public function getCategory($categoryIDs)
    {
        return Category::whereIn('productCategoryID', [$categoryIDs])->get();
    }
    public function insertcategory($categoryData)
    {

        return SourceCategory::updateOrCreate([
            'categoryID' => $categoryData->productCategoryID,
            'categoryPatentId' => $categoryData->parentCategoryID
        ], [
            'categoryID' => $categoryData->productCategoryID,
            'categoryPatentId' => $categoryData->parentCategoryID,
            'categoryTitle' => $categoryData->productCategoryName,
            // 'image'=>$productData->category->,
            // 'slug'=>$productData->category->,
            // 'categoryTags'=>$productData->category->,
            'shopifyPendingProcess' => 1,
            'lastSyncDate' => date('Y-m-d H:i:s')

        ]);
    }

    public function insertVariants($variant, $product_id)
    {

        try {
            $status = 0;
            if (
                $variant->active == 1 &&
                $variant->displayedInWebshop == 1 &&
                $variant->erplyDeleted == 0 &&
                $variant->variationDescription != ''
            ) {
                $status = 1;
            }


            $colorSize = $this->getColorSize($variant->variationDescription);

            return   SourceVariant::updateOrCreate([
                // 'sku' => $variant->code,
                // 'barcode' => $variant->code2,
                'variantId' => $variant->productID
            ], [
                'product_id' => $product_id,
                'variantId' => $variant->productID,
                'sku' => $variant->code,
                'barcode' => $variant->code2,
                'weight' => $variant->netWeight,
                'price' => $variant->price,
                'priceWithTax' => $variant->priceWithVat,
                'color' => $colorSize['color'],
                'size' => $colorSize['size'],
                'colorOrder' => $variant->colorOrder,
                'sizeOrder' => $variant->sizeOrder,
                'shopifyPendingProcess' => $status,
                'colorID' => $variant->variationDimID1
                // 'sohPendingProcess' => 1,
                // 'pricePendingProcess' => 1,

            ]);
        } catch (Exception  $th) {
            info($th->getMessage());
            return $th->getMessage();
        }
    }

    private function getColorSize($datas)
    {

        $attributes = json_decode($datas, true);
        $color = null;
        $size = null;
        if (!empty($attributes)) {
            // Iterate through each attribute
            foreach ($attributes as $attribute) {
                // Check if the attribute is for color
                if ($attribute['name'] === 'Color') {
                    $color = $attribute['value'];
                }
                // Check if the attribute is for size
                if ($attribute['name'] === 'Size') {
                    $size = $attribute['value'];
                }
            }
        }
        return [
            'color' => $color,
            'size' => $size
        ];
    }

    public function insertLocation($location)
    {

        return SourceLocation::updateOrCreate([
            'warehouseID' => $location->warehouseID
        ], [
            'name' => $location->name,
            'address' => $location->address,
            'address2' => $location->address2,
            'street' => $location->street,
            'city' => $location->city,
            'zipCode' => $location->ZIPcode,
            'state' => $location->state,
            'country' => $location->country,
            'phone' => $location->phone,
            'email' => $location->email,
            'bankAccountNumber' => $location->bankAccountNumber,
            'timeZone' => $location->timeZone,
            'shopifyLocationId' => $location->shopifyId
        ]);
    }
    public function getLocationsById($id)
    {

        return SourceLocation::where('warehouseID', $id)->first();
    }

    public function getAllLocations()
    {
        return SourceLocation::all();
    }



    public function insertImages($image, $product_id, $variant_id, $imageName = null)
    {

        try {
            return SourceImage::updateOrCreate([
                'pictureID' => $image->productPictureID
            ], [
                'pictureID' => $image->productPictureID,
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'colorID' => $image->colourID ?? 0,
                'name' => $imageName ?? $image->key ?? '',
                'pendingProcess' => 1,
                'isDeleted' => $image->isDeleted,
                'order' => $image->order,
                'lastsyncDate' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception  $th) {
            info($th->getMessage());
            return $th->getMessage();
        }
    }

    public function insertSoh($variant_id, $locationId,  $data)
    {
        return SourceSoh::updateOrCreate([
            'variant_id' => $variant_id,
            'location_id' => $locationId
        ], $data);
    }

    public function checkLocationSoh($variant_id, $locationId)
    {
        return SourceSoh::where([
            'variant_id' => $variant_id,
            'location_id' => $locationId
        ])->first();
    }
}
