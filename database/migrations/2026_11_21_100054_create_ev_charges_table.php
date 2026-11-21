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
        Schema::create('ev_charges', function (Blueprint $table) {
            $table->id();
            $table->decimal('current_battery', 5, 2);
            $table->bigInteger("price_charge");
            $table->text('note');
            $table->text('url_payment_receipt');
            $table->foreignId('rent_id')->constrained("rents")->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ev_charges');
    }
};
