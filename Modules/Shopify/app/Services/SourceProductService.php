<?php

namespace Modules\Shopify\Services;

use Modules\Shopify\Models\Source\SourceImage;
use Modules\Shopify\Models\Source\SourceProduct;
use Modules\Shopify\Models\source\SourceVariant;

class SourceProductService
{
    public function getAllProduct($limit = 1)
    {
        return SourceProduct::with([
            'variants' => function ($query) {
                $query->where('shopifyPendingProcess', 1)
                    ->where('priceWithTax', '>', 0)
                    ->orderBy('colorOrder')->orderBy('sizeOrder');
            }
        ])->where([
            'shopifyPendingProcess' => 1
        ])
            ->orderby('lastPushedDate', 'ASC')
            ->limit($limit)
            ->get();
    }
    public function getPendingProduct($condition, $limit = 1)
    {

        return SourceProduct::with([
            'variants' => function ($query) {
                $query->where('shopifyPendingProcess', 1)
                    ->orderBy('colorOrder')->orderBy('sizeOrder');
            }
        ])->where($condition)
            ->orderby('lastSyncDate', 'ASC')
            ->limit($limit)
            ->get();
    }

    public function getPendingProductAppend($condition, $limit = 1)
    {

        return SourceProduct::with([
            'variants' => function ($query) {
                $query->where('shopifyPendingProcess', 1)
                    ->orderBy('colorOrder')->orderBy('sizeOrder');
            }
        ])->where($condition)
            ->orderby('lastSyncDate', 'ASC')
            ->limit($limit)
            ->get();
    }
    public function getSohPendingProduct($condition, $limit = 1)
    {

        return SourceProduct::with([
            'variants' => function ($query) {
                $query->where('shopifyPendingProcess', 1)

                    ->orderBy('colorOrder')->orderBy('sizeOrder');
            }
        ])->where($condition)->where('shopifyProductId', '!=', null)
            ->orderby('lastSyncDate', 'DESC')
            ->limit($limit)
            ->get();
    }
    public function getPricePendingProduct($condition, $limit = 1)
    {
        return SourceProduct::with([
            'variants' => function ($query) {
                $query->where('shopifyPendingProcess', 1)
                    ->where('priceWithTax', '>', 0)
                    ->orderBy('colorOrder')->orderBy('sizeOrder');
            }
        ])->where($condition)->where('shopifyProductId', '!=', null)
            ->orderby('lastSyncDate', 'ASC')
            ->limit($limit)
            ->get();
    }
    public function getImagePendingProduct($condition, $limit)
    {

        return SourceProduct::with([
            'images' => function ($query) {
                $query->where('isDeleted', '=', 0)
                    ->distinct('name')
                    ->orderBy('colorID', 'asc')
                    ->orderBy('order', 'asc');
            }
        ])->where($condition)->where('shopifyProductId', '!=', null)
            ->orderby('lastPushedDate', 'ASC')
            ->limit($limit)
            ->get();
    }


    public function getProduct($code)
    {
        return SourceProduct::with([
            'variants' => function ($query) {
                $query->where('shopifyPendingProcess', 1)
                    ->where('priceWithTax', '>', 0)
                    ->orderBy('colorOrder')->orderBy('sizeOrder');
            }
        ])->where('handle', $code)->get();
    }

    #update by id
    public function updateProduct($id, $data)
    {
        return SourceProduct::where('id', $id)->update($data);
    }

    public function updateVariants($sku, $data)
    {
        return SourceVariant::where('sku', $sku)->update($data);
    }

    public function updateImage($condition, $data)
    {
        return SourceImage::where($condition)->update($data);
    }
}
