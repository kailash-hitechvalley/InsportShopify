<?php

namespace Modules\Shopify\Traits;

use Exception;
use GuzzleHttp\Client;
use Modules\Shopify\Models\ShopifyCursor;
use PhpParser\Node\Stmt\Return_;
use Illuminate\Support\Str;

trait ShopifyCollectionMutationTrait
{

    public function __construct()
    {
    }
    # this function is used to create or update collection as per the shopifyCollectionId
    public function createOrUpdateCollectionMutation($collectionData)
    {

        $mutation = 'mutation {';

        if ($collectionData->shopifyCollectionId) {
            $mutation .= 'collectionUpdate(input:{';
            $mutation .= 'id:"' . $collectionData->shopifyCollectionId . '",';
        } else {
            $mutation .= 'collectionCreate(input:{';
        }

        $mutation .= 'title:"' . $collectionData->categoryTitle . '",';
        $mutation .= 'descriptionHtml:"' . $collectionData->descriptionHtml . '",';
        $mutation .= 'handle:"' . ($collectionData->slug ?? Str::slug($collectionData->categoryTitle)) . '",';

        $mutation .= 'ruleSet:{';
        $mutation .= 'appliedDisjunctively: true,';
        $rules = $this->generateCollectionRules($collectionData->sourceChildrenCategory, $collectionData->categoryTags ?? $collectionData->categoryTitle);

        $mutation .= 'rules:[' . implode('', $rules) . ']';
        $mutation .= '}';
        $mutation .= '}) {';

        $mutation .= 'collection {';
        $mutation .= 'id';
        $mutation .= '}';
        $mutation .= 'userErrors {';
        $mutation .= 'field ';
        $mutation .= 'message';
        $mutation .= '}';
        $mutation .= '}';
        $mutation .= '}';
        echo $mutation;

        return $mutation;
    }
    # this function will generate rules for the collection
    private function generateCollectionRules($ChildrenCategories, $cTag,  $hasStaticRule = false)
    {
        foreach ($ChildrenCategories as $rule) {
            $tag = $rule->categoryTags ?? $rule->categoryTitle;
            $str = '{';
            $str .= 'column: "TAG",';
            $str .= 'relation: "EQUALS",';
            $str .= 'condition:"' . $tag . '"';
            $str .= '},';
            $dynamicRules[] = $str;
            echo "has " . count($rule->sourceChildrenCategory) . " chieldren category inside" . "<br>";
            #Check if there are descendants
            if (count($rule->sourceChildrenCategory) > 0) {
                # Call the function recursively for descendants
                $dynamicRules = array_merge($dynamicRules, $this->generateCollectionRules($rule->sourceChildrenCategory, null, true));
            }
        }
        if (!$hasStaticRule) {
            $strLast = '{';
            $strLast .= 'column: "TAG",';
            $strLast .= 'relation: "EQUALS",';
            $strLast .= 'condition:"' . $cTag . '"';
            $strLast .= '}';
            $dynamicRules[] = $strLast;
            $hasStaticRule = true; // Update the flag to indicate the static rule has been added
        }

        return $dynamicRules;
    }

    # delete collection
    public function deleteCollection($collectionId)
    {
        $mutation = 'mutation {';
        $mutation .= 'collectionDelete(id:"' . $collectionId . '") {';
        $mutation .= 'deletedCollectionId';
        $mutation .= '}';
        $mutation .= '}';
        return $mutation;
    }
    # get all collection from the shopify
    public function getAllCollection()
    {
        $query = 'query {';
        $query .= 'collections(first: 100) {';
        $query .= 'edges {';
        $query .= 'node {';
        $query .= 'id';
        $query .= 'title';
        $query .= '}';
        $query .= '}';
        $query .= '}';
        $query .= '}';

        return $query;
    }
}
