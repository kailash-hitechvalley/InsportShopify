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

    public function getShopifyProducts($pid, $limit = 3)
    {

        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, 'GetProductCursor', $this->live);
        $myquery = $pid ? 'query: "id:' . $pid . '"' : '';
        $after = $cursor ? ', after: "' . $cursor . '"' : '';
        if ($myquery) {
            $after = $myquery;
        }
        $query = '{
         products(first: ' . $limit . ', sortKey:ID ' . $after . ' ) {
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

    public function getShopifyLocations()
    {
        $query = '{
            locations(first: 100) {
              edges {
                node {
                  id
                  name
                }
              }
            }
          }';

        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }
    public function getSoh()
    {
        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, 'SOH', $this->live) ?? '';

        $productsQuery = 'products(first:1 , sortKey: ID';

        if ($cursor !== '') {
            $productsQuery .= ', after:1 "' . $cursor . '"';
        }

        $productsQuery .= ')';

        $query = <<<GQL
        query {
            $productsQuery {
                edges {
                    node {
                        id
                        title
                        updatedAt
                        variants(first: 100) {
                            edges {
                                node {
                                    id
                                    title
                                    inventoryItem {
                                        id
                                        updatedAt
                                        inventoryLevels(first: 10) {
                                            edges {
                                                node {
                                                    available
                                                    updatedAt
                                                    location {
                                                        id
                                                        name
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
        }
    GQL;
        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }
}
