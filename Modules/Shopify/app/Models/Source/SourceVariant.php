<?php

namespace Modules\Shopify\Models\Source;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shopify\Models\Source\SourceProduct;

class SourceVariant extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'source_variants';
    protected $primaryKey = 'id';

    protected $guarded = [];

    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(SourceProduct::class, 'product_id', 'id');
    }

    # has many source images
    public function images()
    {
        return $this->hasMany(SourceImage::class, 'variant_id', 'id');
    }

    public function sourceSoh()
    {
        return $this->hasMany(SourceSoh::class, 'variant_id', 'id');
    }
}
