<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mi_order_party', function (Blueprint $table) {
            $table->bigIncrements('party_id');

            $table->string('party_gstn', 50)->nullable();
            $table->string('party_name', 100)->nullable();
            $table->string('party_legal_name', 200)->nullable();
            $table->string('party_type', 10)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mi_order_party');
    }
};
