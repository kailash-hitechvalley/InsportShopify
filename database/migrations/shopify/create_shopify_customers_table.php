<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_customers', function (Blueprint $table) {
                        $table->id();
                        $table->string('shopify_member_id')->nullable()->index();
                        $table->string('first_name')->nullable();
                        $table->string('last_name')->nullable();
                        $table->string('email')->nullable()->index();
                        $table->string('status')->nullable();
                        $table->string('display_name')->nullable();
                        $table->datetime('customer_created_at')->nullable();
                        $table->datetime('customer_updated_at')->nullable();
                        $table->string('phone')->nullable();
                        $table->string('state')->nullable();
                        $table->text('tags')->nullable();
                        $table->string('address1')->nullable();
                        $table->string('address2')->nullable();
                        $table->string('city')->nullable();
                        $table->string('company')->nullable();
                        $table->string('country')->nullable();
                        $table->string('country_code', 2)->nullable();
                        $table->string('zipcode')->nullable();
                        $table->string('province')->nullable();
                        $table->string('province_code')->nullable();
                        $table->text('smsMarketingConsent')->nullable();
                        $table->text('emailMarketingConsent')->nullable();
                        $table->string('amountSpent')->nullable();
                        $table->string('totalSpentCurrencyCode')->nullable();
                        $table->string('storeCreditAccounts')->nullable();
                        $table->tinyint('isDetailChanged')->default(0);
                        $table->timestamps();
                  });
      }

      public function down()
      {
            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_customers');
      }
};
