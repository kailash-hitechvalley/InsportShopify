<?php

namespace Modules\GetShopifyData\Services;

use Modules\Shopify\Traits\ShopifyTrait;

class ShopifyGetService
{
    use ShopifyTrait;

    protected $live = 1;
    public function __construct() {}

    public function getShopifyProducts($pid, $limit = 3, $cursorName, $debug = 0)
    {

        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, $cursorName, $this->live);

        $after = $cursor ? ', after: "' . $cursor . '"' : '';

        if ($cursorName == 'GetProductUpdatedBYCursor'  && $cursor) {
            $magic = "'" . $cursor . "'";

            $after = ', query: "updated_at:>=' . $magic  . '"';
        }

        $myquery = $pid ? ' ,query: "id:' . $pid . '"' : '';

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

                }
                }
            }
        }';
        if ($debug == 5) {
            dd($query);
        }
        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }
    // variants(first:100) {
    //     edges {
    //         node {
    //         id
    //         sku
    //         title
    //         barcode
    //         compareAtPrice
    //         price
    //         inventoryQuantity
    //         selectedOptions{
    //             name
    //             value
    //         }
    //         inventoryItem {
    //             id
    //             inventoryLevels(first:100)  {
    //             edges {
    //                 node {
    //                 location {
    //                     activatable
    //                     id
    //                 }
    //                 }
    //             }
    //             }
    //         }
    //         }
    //     }
    //     }
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

    public function getShopifyVariants($limit = 3, $debug, $cursorName)
    {
        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, $cursorName, $this->live);
        if ($cursorName == 'GetProductVariantsCursor' && $cursor) {

            $after = $cursor ? ', after: "' . $cursor . '"' : '';
        } else {
            $magic = "'" . $cursor . "'";
            $after = ', query: "updated_at:>=' . $magic  . '"';
        }

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
                    createdAt
                    updatedAt

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
        if ($debug == 1) {
            dd($query);
        }
        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }
}
