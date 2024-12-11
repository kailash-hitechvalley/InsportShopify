<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_order_products', function (Blueprint $table) {
                        $table->id();
                        $table->string('shopify_lineItem_id');
                        $table->string('shopify_order_id');
                        $table->string('shopify_product_id')->nullable();
                        $table->string('shopify_variant_id')->nullable();
                        $table->string('stockCode')->nullable();
                        $table->string('sku')->nullable();
                        $table->string('color')->nullable();
                        $table->string('size')->nullable();
                        $table->integer('quantity');
                        $table->decimal('unit_price', 10, 2)->nullable();
                        $table->decimal('total_price', 10, 2)->nullable();
                        $table->decimal('total_discount', 10, 2)->nullable();
                        $table->timestamps();
                  });
      }

      public function down()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_order_products');
      }
};
