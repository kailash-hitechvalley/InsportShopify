<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_webhooks', function (Blueprint $table) {
                        $table->id();
                        $table->string('webhookName')->nullable();
                        $table->string('topic')->nullable();
                        $table->string('webhookUrl')->nullable();
                        $table->tinyInteger('isLive')->default(0);
                        $table->string('shopifyWebhookId')->nullable();
                        $table->string('includeFields')->nullable()->comment('comma separated on " "');
                        $table->timestamps();
                  });
      }

      public function down()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_webhooks');
      }
};
