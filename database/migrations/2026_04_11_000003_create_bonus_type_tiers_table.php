<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_type_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bonus_type_id')->constrained('bonus_types')->cascadeOnDelete();
            $table->unsignedInteger('level');
            $table->string('label', 255)->nullable();
            $table->decimal('qualifier_value', 14, 2)->nullable();
            $table->string('qualifier_type', 50)->nullable();
            $table->decimal('rate', 8, 4)->nullable();
            $table->decimal('amount', 12, 4)->nullable();
            $table->timestamps();

            $table->index(['bonus_type_id', 'level'], 'idx_bonus_type_tiers_bonus_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_type_tiers');
    }
};
