<?php

namespace Modules\GetShopifyData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\GetShopifyData\Database\Factories\ShopifySOHFactory;

class ShopifySOH extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = "shopify_soh";
    protected $gaurded = [];
    protected $fillable = [];
}
