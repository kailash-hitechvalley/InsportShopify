<?php

namespace Modules\Shopify\Models\Source;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SourceSoh extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'source_soh';
    protected $primaryKey = 'id';

    protected $fillable = [
        'product_id',
        'variant_id',
        'varinatId',
        'location_id',
        'currentStock',
        'lastStockUpdate',
        'lastPushedDate',
        'pendingProcess'
    ];

    public function variant()
    {
        return $this->belongsTo(SourceVariant::class, 'product_id');
    }

    public function product()
    {
        return $this->belongsTo(SourceProduct::class, 'product_id');
    }
    public function location()
    {
        return $this->hasOne(SourceLocation::class, 'id', 'location_id');
    }

    public function availableQuantity()
    {
    }
}
