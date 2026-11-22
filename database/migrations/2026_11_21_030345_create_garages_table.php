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
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string('garage_code', 4)->unique();
            // Only for garage EV car
            $table->bigInteger('price_kwh')->nullable();
            $table->bigInteger('price');
            $table->enum('category', [
                'Bike',
                'Car',
                'Electric Car',
            ]);
            $table->enum('status', ['available', 'used'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
