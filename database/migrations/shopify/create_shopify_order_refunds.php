<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_order_refunds', function (Blueprint $table) {
                        $table->id();
                        $table->string('shopify_order_id');
                        $table->string('shopify_refund_id');
                        $table->string('order_number');
                        $table->datetime('order_updated_date')->nullable();
                        $table->datetime('refund_created_exact')->nullable();
                        $table->string('total_refunded')->nullable();
                        $table->string('refund_type')->nullable();
                        $table->text('refund_reason')->nullable();
                        $table->string('refund_shipping_amount')->nullable();
                        $table->timestamps();
                  });
      }

      public function down()
      {
            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_order_refunds');
      }
};
