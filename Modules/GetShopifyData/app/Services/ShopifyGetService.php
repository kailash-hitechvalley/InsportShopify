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

    public function getShopifyProducts($pid, $limit = 3, $cursorName)
    {

        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, $cursorName, $this->live);

        $after = $cursor ? ', after: "' . $cursor . '"' : '';

        if ($cursorName == 'GetProductUpdatedBYCursor'  && $cursor) {

            $after = ', query: "updated_at:>=' . $cursor  . '"';
        }

        $myquery = $pid ? 'query: "id:' . $pid . '"' : '';

        if ($myquery) {
            $after = $myquery;
        }

        $query = '{
         products(first: ' . $limit . ', sortKey:UPDATED_AT ' . $after . ' ) {
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


    public function getShopifyVariants($limit)
    {

        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, 'GetProductVariantsCursor', $this->live) ?? '';

        $magic = 'productVariants(first:' . $limit . ',sortKey:ID)';

        if ($cursor != '') {
            $magic = 'productVariants(first:' . $limit . ',sortKey:ID, after:"' . $cursor . '")';
        }

        $magic .= ' {';

        $query = <<<GQL
          query {
              $magic
              edges {
                  cursor
                  node {
                      id
                      title
                      product {
                          id
                          status
                      }
                      price
                      selectedOptions {
                          name
                          value
                      }
                      defaultCursor
                      inventoryItem {
                          id
                      }
                      inventoryQuantity
                      availableForSale
                      compareAtPrice
                      createdAt
                      updatedAt
                      displayName
                      sku
                      barcode
                      image {
                          url
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
}
