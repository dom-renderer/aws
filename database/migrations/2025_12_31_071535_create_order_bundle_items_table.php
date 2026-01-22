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
        Schema::create('order_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('source_product_id')->nullable();
            $table->unsignedBigInteger('source_variant_id')->nullable();
            $table->double('quantity')->default(0);
            $table->tinyInteger('unit_type')->default(0)->comment('0 = Base Unit | 1 = Additional Unit');
            $table->unsignedBigInteger('unit_id')->nullable();

            $table->string('product_name')->nullable()->comment('snapshot');
            $table->string('product_sku')->nullable()->comment('snapshot');
            $table->string('unit_name')->nullable()->comment('snapshot');

            $table->double('fulfilled_quantity')->default(0);
            $table->enum('fulfillment_status', ['unfulfilled','partial','fulfilled'])->default('unfulfilled');

            $table->softDeletes();
            $table->timestamps();

            $table->index('order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_bundle_items');
    }
};
