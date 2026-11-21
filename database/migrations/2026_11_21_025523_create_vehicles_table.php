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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('license_plate')->nullable();
            // For electric vehicles, we can store the total kWh consumed
            $table->integer('total_kwh')->nullable();
            $table->string('image')->nullable();
            $table->enum('category', [
                'Bike',
                'Motor Cycle',
                'Car',
                'Electric Car',
            ]);
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
