<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_order_deliveries', function (Blueprint $table) {
                        $table->id();
                        $table->string('shopify_order_id')->nullable();
                        $table->string('shopify_member_id')->nullable();
                        $table->string('first_name')->nullable();
                        $table->string('last_name')->nullable();
                        $table->string('phone')->nullable();
                        $table->string('email')->nullable();
                        $table->string('street')->nullable();
                        $table->string('suburb')->nullable();
                        $table->string('post_code')->nullable();
                        $table->string('country')->nullable();
                        $table->string('city')->nullable();
                        $table->string('state')->nullable();
                        $table->timestamps();
                  });
      }

      public function down()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_order_deliveries');
      }
};
