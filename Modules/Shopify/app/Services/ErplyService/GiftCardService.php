<?php

namespace Modules\Shopify\Services\ErplyService;

use Modules\Shopify\Traits\ShopifyTrait;

class GiftCardService
{
    protected $live = 1;
    use ShopifyTrait;
    public  function  metadata($model, $limit = 2, $condition = null, $sortColumn  = null, $sortOrder = 'DESC')
    {
        return $model
            ->when($condition != null, function ($query) use ($condition) {
                $query->where($condition);
            })

            ->when($sortColumn != null, function ($query) use ($sortColumn, $sortOrder) {
                $query->orderBy($sortColumn, $sortOrder);
            })

            ->take($limit)
            ->get();
    }

    public  function  createOrUpdateGiftCard($giftCard)
    {

        $giftCode = $giftCard->code ?? '123456789';
        $initialValue = $giftCard->initialValue ?? 10;
        $expiresOn = $giftCard->expiresOn ?? '2024-07-16';
        $customerId = $giftCard->customerId ?? 'gid://shopify/Customer/6943830868249';

        $note = $giftCard->note ?? 'gift card created by Erply FOR TEST';

        if (isset($giftCard->shopifyGiftCardId) && $giftCard->shopifyGiftCardId) {
            $gid = $giftCard->shopifyGiftCardId;
            $query = 'mutation { giftCardUpdate( id:"' . $gid . '", input:{';
        } else {
            $query = 'mutation {
                 giftCardCreate(input: {';
        }
        $query .= ' code: "' . $giftCode . '",
                    initialValue: "' . $initialValue . '",
                    expiresOn: "' . $expiresOn . '",
                    customerId: "' . $customerId . '",
                    note: "' . $note . '"
                 }) {
                     userErrors {
                        message
                        field
                     }
                     giftCard {
                      id
                      expiresOn
                      note
                    }
                 }}';
        dump($query);
        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }

    public  function  getShopifyCustomer($limit)
    {
        $clientCode = $this->getClientCode();
        $cursor = $this->getCursor($clientCode, 'customerCursor', $this->live);

        $after = $cursor ? ', after: "' . $cursor . '"' : '';
        $query = '{
               customers(first: ' . $limit . ', sortKey: CREATED_AT' . $after . ') {
                edges {
                    node {
                        id
                        firstName
                        lastName
                        state
                        createdAt
                        email
                        updatedAt
                        defaultAddress {
                        address1
                        address2
                        phone
                        province
                        provinceCode
                        zip
                        country
                        city
                        }
                    }
                    cursor
                    }
            }
            }';
        dump($query);
        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }

    public  function getShopifySingleCustomer($gid)
    {
        $query = '{
                  customer(id: "' . $gid . '") {
                    id
                    firstName
                    lastName
                    email
                    phone
                  }
                }';

        return $this->sendShopifyQueryRequestV2('POST', $query, $this->live);
    }
}
