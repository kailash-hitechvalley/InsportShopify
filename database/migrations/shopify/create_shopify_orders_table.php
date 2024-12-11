<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {;
            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_orders', function (Blueprint $table) {
                        $table->id();
                        $table->string('shopify_order_id');
                        $table->string('shopify_member_id')->nullable();
                        $table->string('pendingProcess')->default(1);
                        $table->string('order_number')->nullable();
                        $table->string('fullfillment_status')->nullable();
                        $table->string('financial_status')->nullable();
                        $table->decimal('total_price', 10, 2);
                        $table->text('note')->nullable();
                        $table->decimal('total_discounts', 10, 2)->nullable();
                        $table->string('currency_code');
                        $table->decimal('subtotal_price', 10, 2);
                        $table->decimal('total_tax', 10, 2);
                        $table->string('payment_gateway_names')->nullable();
                        $table->string('shipping_method')->nullable();
                        $table->string('total_shipping')->nullable();
                        $table->text('risk_level')->nullable();
                        $table->string('total_items')->nullable();
                        $table->string('coupon_code')->nullable();
                        $table->string('coupon_amount')->nullable();
                        $table->datetime('order_created_at')->nullable();
                        $table->datetime('order_updated_at')->nullable();
                        $table->datetime('processed_at')->nullable();
                        $table->text('channelInformation')->nullable();
                        $table->text('channels')->nullable();
                        $table->text('fulfillments_location')->nullable();
                        $table->timestamps();
                  });
      }

      public function down()
      {;
            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_orders');
      }
};
