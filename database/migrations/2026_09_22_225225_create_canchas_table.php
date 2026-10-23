<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('canchas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('surface', 60)->nullable();
            $table->decimal('price_per_hour', 10, 2);
            $table->time('opens_at');
            $table->time('closes_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canchas');
    }
};
