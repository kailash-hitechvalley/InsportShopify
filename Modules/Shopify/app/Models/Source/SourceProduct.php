<?php

namespace Modules\Shopify\Models\Source;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Shopify\Models\Source\SourceCategorie;
use Modules\Shopify\Models\Source\SourceCategory;
use Modules\Shopify\Models\Source\SourceVariant;

class SourceProduct extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'stockId',
        'category_id',
        'handle',
        'productType',
        'vendor',
        'productTags',
        'brand',
        'title',
        'descriptionHtml',
        'isMatrix',
        'status',
        'mainImage',
        'countVariants',
        'shopifyPendingProcess',
        'sohPendingProcess',
        'pricePendingProcess',
        'imagePendingProcess',
        'varinatsAppendPending',
        'lastSyncDate',
        'lastPushedDate',
        'shopifyProductId',
        'errorMessage',
        'sourceAddedDate',
        'sourceUpdatedDate'
    ];
    public function sourceCategory(): BelongsTo
    {
        return $this->belongsTo(SourceCategory::class);
    }
    public function variants(): HasMany
    {
        return $this->hasMany(SourceVariant::class, 'product_id', 'id');
    }

    #image relation
    public function images(): HasMany
    {
        return $this->hasMany(SourceImage::class, 'product_id', 'id');
    }
}
