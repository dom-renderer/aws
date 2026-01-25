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
        Schema::create('aw_product_substitutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('aw_products')->onDelete('cascade');
            $table->foreignId('substitute_id')->constrained('aw_products')->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aw_product_substitutes');
    }
};
