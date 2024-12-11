<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_order_refund_items', function (Blueprint $table) {
                        $table->id();
                        $table->string('shopify_refund_id');
                        $table->string('shopify_product_id')->nullable();
                        $table->string('shopify_lineItem_id')->nullable();
                        $table->integer('product_quantity')->nullable();
                        $table->string('sku')->nullable();
                        $table->decimal('product_price', 10, 2)->nullable();
                        $table->timestamps();
                  });
      }

      public function down()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_order_refund_items');
      }
};
