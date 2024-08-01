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

    public function getShopifyProducts($pid, $limit = 3, $cursorName, $debug = 0)
    {

        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, $cursorName, $this->live);

        $after = $cursor ? ', after: "' . $cursor . '"' : '';

        if ($cursorName == 'GetProductUpdatedBYCursor'  && $cursor) {
            $magic = "'" . $cursor . "'";

            $after = ', query: "updated_at:>=' . $magic  . '"';
        }

        $myquery = $pid ? 'query: "id:' . $pid . '"' : '';

        if ($myquery) {
            $after = $myquery;
        }

        $query = '{
         products(first: ' . $limit . ', sortKey:UPDATED_AT' . $after . ' ) {
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
                    updatedAt
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
        if ($debug == 5) {
            dd($query);
        }
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

        $productsQuery = 'products(first:1, sortKey: ID';

        if ($cursor !== '') {
            $productsQuery .= ', after: "' . $cursor . '"';
        }

        $productsQuery .= ')';

        $query = <<<GQL
        query {
            $productsQuery {
                edges {
                    cursor
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
                                        inventoryLevels(first: 100) {
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
                    pageInfo {
                    hasNextPage
                    hasPreviousPage
                    startCursor
                    endCursor
                }
            }
        }
        GQL;

        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }

    public function getShopifyVariants($limit = 3)
    {
        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, 'GetProductVariantsCursor', $this->live);

        $after = $cursor ? ', after: "' . $cursor . '"' : '';

        $query = '{
            productVariants(first: ' . $limit . $after . ') {
                edges {
                cursor
                node {
                    id
                    title
                    price
                    compareAtPrice
                    sku
                    barcode
                    inventoryQuantity
                    inventoryItem {
                        id
                    }
                    selectedOptions {
                        name
                        value
                    }
                        product {
                            id
                        }
                }
                }
            }
        }';
        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }
}
