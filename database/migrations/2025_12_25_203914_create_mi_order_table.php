<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mi_order', function (Blueprint $table) {
            $table->bigIncrements('order_id');

            $table->decimal('taxable_amount', 10, 2)->default(0);
            $table->decimal('total_invoice_value', 10, 2)->default(0);
            $table->decimal('other_value', 10, 2)->default(0);

            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);

            $table->string('transport_mode', 100)->nullable();

            $table->unsignedBigInteger('receiver_id')->default(0);
            $table->unsignedBigInteger('sender_id')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mi_order');
    }
};
