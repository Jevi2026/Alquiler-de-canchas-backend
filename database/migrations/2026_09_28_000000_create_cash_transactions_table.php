<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('type', 12);
            $table->string('payment_method', 20);
            $table->decimal('amount', 10, 2);
            $table->string('description', 255)->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['occurred_at', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
