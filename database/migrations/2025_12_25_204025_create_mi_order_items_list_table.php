<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mi_order_items_list', function (Blueprint $table) {
            $table->bigIncrements('item_id');

            $table->unsignedBigInteger('order_id');

            $table->string('product_name', 100)->nullable();
            $table->text('product_description')->nullable();
            $table->string('hsn_code', 100)->nullable();
            $table->string('unit_of_product', 100);

            $table->integer('product_quantity')->default(0);

            $table->decimal('taxable_amount', 10, 2)->default(0);
            $table->decimal('other_value', 10, 2)->default(0);

            $table->timestamps();

            $table->foreign('order_id')
                ->references('order_id')
                ->on('mi_order')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mi_order_items_list');
    }
};
