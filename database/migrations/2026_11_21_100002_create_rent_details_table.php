<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rent_details', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['monthly', 'daily'])->default('daily');

            $table->date('start_date');
            $table->date('end_date');
            $table->integer("total_day");

            $table->bigInteger("total_price");

            $table->boolean('is_extension')->default(false);
            $table->foreignId('rent_id')->constrained("rents")->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_details');
    }
};
