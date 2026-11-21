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
        Schema::create('rents', function (Blueprint $table) {
            $table->id();
            $table->integer('total_day');
            $table->date('start_date')->useCurrent();
            $table->date('end_date')->nullable();

            $table->bigInteger('total_price');
            $table->text('note')->nullable();
            // FK Section
            $table->foreignId('renter_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('garage_id')->constrained('garages')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rents');
    }
};
