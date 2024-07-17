<?php

namespace Modules\Shopify\Models\ReadShopify;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopifyCustomer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected $guarded = [];
    protected $table = 'shopify_customers';
}
