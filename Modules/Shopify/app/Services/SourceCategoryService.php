<?php

namespace Modules\Shopify\App\Services;

use Modules\Shopify\App\Models\Source\SourceCategory;
use Modules\Shopify\Database\factories\Source\SourceCategoryFactory;

class SourceCategoryService
{
    public function getAllCategory()
    {
        return SourceCategory::where([
            'shopifyPendingProcess' => 1
        ])->get();
    }
    public function getCategory($id)
    {

        return SourceCategory::where('id', $id)->get();
    }
    public function updateCategory($data, $id)
    {
        $category = SourceCategory::find($id);
        return  $category->update($data);
    }
}
