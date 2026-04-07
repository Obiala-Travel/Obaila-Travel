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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['flight', 'hotel']);
            $table->string('reference')->unique();           // e.g. SR9X2KA1
            $table->string('provider_booking_id')->nullable(); // Duffel order id
            $table->string('origin', 10)->nullable();        // IATA code
            $table->string('destination', 10)->nullable();   // IATA code
            $table->dateTime('depart_at')->nullable();
            $table->dateTime('return_at')->nullable();
            $table->unsignedTinyInteger('passengers')->default(1);
            $table->string('cabin_class', 30)->nullable();
            $table->decimal('total_price', 10, 2);
            $table->string('currency', 5)->default('USD');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->json('raw_response')->nullable();        // full Duffel payload
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
