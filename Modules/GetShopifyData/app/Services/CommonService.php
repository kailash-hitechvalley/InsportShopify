<?php

namespace Modules\GetShopifyData\app\Services;

use Modules\Shopify\App\Models\ShopifyCursor;
use Modules\Shopify\App\Traits\ShopifyTrait;

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
