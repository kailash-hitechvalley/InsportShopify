<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

      public function up()
      {

            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)
                  ->create('shopify_cursor', function (Blueprint $table) {
                        $table->id();
                        $table->string('clientCode')->nullable();
                        $table->string('cursorName')->nullable();
                        $table->string('cursor')->nullable();
                        $table->tinyInteger('isLive')->default(0);
                        $table->timestamps();
                  });
      }

      public function down()
      {
            $conn = config('retailcareshopify.connection', 'mysql');
            Schema::connection($conn)->dropIfExists('shopify_cursor');
      }
};
