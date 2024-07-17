<?php

namespace Modules\GetShopifyData\Services;

use Modules\Shopify\Models\ShopifyCursor;
use Modules\Shopify\Traits\ShopifyTrait;

class CommonService
{
    use ShopifyTrait;
    public function __construct()
    {
    }
    public function updateCreateProduct($model, $condition, $datas)
    {

        return $model::updateorCreate(
            $condition,
            $datas
        );
    }
    public function saveCursor($cursor, $name, $isLive = 0)
    {

        $clientCode = $this->getClientCode();
        if ($cursor) {
            ShopifyCursor::updateOrCreate(
                [
                    'clientCode' => $clientCode,
                    'cursorName' => $name,
                ],
                [
                    'clientCode' => $clientCode,
                    'cursorName' => $name,
                    'isLive' => $isLive,
                    'cursor' => $cursor,

                ]
            );
        }
    }
}
