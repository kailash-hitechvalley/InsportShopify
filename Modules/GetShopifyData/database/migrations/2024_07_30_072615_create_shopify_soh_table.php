<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shopify_soh', function (Blueprint $table) {
            $table->id();
            $table->string('clientCode')->nullable();
            $table->integer('isLive')->nullable();
            $table->string('productID')->nullable();
            $table->string('variationID')->nullable();
            $table->string('inventoryID')->nullable();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->string('locationID')->nullable();
            $table->string('locationName')->nullable();
            $table->string('available')->nullable();
            $table->string('lastModified')->nullable();
            $table->string('cursors')->nullable();
            $table->integer('sohPending')->default(0);
            $table->integer('isUpdate')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_soh');
    }
};
