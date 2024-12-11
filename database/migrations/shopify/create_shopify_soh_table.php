<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_soh', function (Blueprint $table) {
                        $table->id();
                        $table->string('inventory_item_id')->nullable();
                        $table->string('location_id')->nullable();
                        $table->integer('available')->default(0)->nullable();
                        $table->string('shopify_product_id')->nullable();
                        $table->string('shopify_variant_id')->nullable();
                        $table->string('sku')->nullable();
                        $table->tinyInteger('pendingProcess')->default(1);
                        $table->timestamps();
                  });
      }

      public function down()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_soh');
      }
};
