<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_errors', function (Blueprint $table) {
                        $table->id();
                        $table->string('errorName')->nullable();
                        $table->string('errorType')->nullable();
                        $table->string('itemId')->nullable()->comment('erply ko ID');
                        $table->string('endpoint')->nullable();
                        $table->text('error')->nullable();
                        $table->text('payload')->nullable();
                        $table->timestamps();
                  });
      }

      public function down()
      {
            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_errors');
      }
};
