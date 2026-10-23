<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cancha_id')->constrained('canchas')->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('total', 10, 2);
            $table->string('status', 20)->default('confirmed');
            $table->string('invoice_number', 40)->unique();
            $table->string('payment_method', 30);
            $table->string('payment_status', 20)->default('pending');
            $table->timestamps();
            $table->index(['cancha_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
