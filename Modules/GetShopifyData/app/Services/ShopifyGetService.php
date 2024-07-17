<?php

namespace Modules\GetShopifyData\Services;

use Modules\Shopify\Traits\ShopifyTrait;

class ShopifyGetService
{
    use ShopifyTrait;

    protected $live = 1;
    public function __construct()
    {
    }

    public function getShopifyProducts($pid)
    {

        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, 'GetProductCursor', $this->live);
        $myquery = $pid ? 'query: "id:' . $pid . '"' : '';
        $after = $cursor ? ', after: "' . $cursor . '"' : '';
        if ($myquery) {
            $after = $myquery;
        }
        $query = '{
         products(first: 10, ' . $after . ' ) {
                edges {
                cursor
                node {
                    id
                    handle
                    status
                    tags
                    title
                    productType
                    createdAt
                    descriptionHtml
                    vendor
                    hasOnlyDefaultVariant
                    totalVariants
                    variants(first:100) {
                    edges {
                        node {
                        id
                        sku
                        title
                        barcode
                        compareAtPrice
                        price
                        inventoryQuantity
                        selectedOptions{
                            name
                            value
                        }
                        inventoryItem {
                            id
                            inventoryLevels(first:100)  {
                            edges {
                                node {
                                location {
                                    activatable
                                    id
                                }
                                }
                            }
                            }
                        }
                        }
                    }
                    }
                }
                }
            }
        }';

        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }
}
