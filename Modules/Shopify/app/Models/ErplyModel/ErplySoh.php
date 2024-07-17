<?php

namespace Modules\Shopify\Models\ErplyModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ErplySoh extends Model
{
    use HasFactory;
    protected $table = 'newsystem_product_soh';
    protected $connection = 'mysql_source';
    protected $timestamp = false;
    protected $fillable = [];
    protected $guarded = [];
}
