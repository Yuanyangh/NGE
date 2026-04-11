<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('compensation_plan_id')->constrained();
            $table->enum('type', ['matching', 'fast_start', 'rank_advancement', 'pool_sharing', 'leadership']);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'compensation_plan_id', 'is_active'], 'idx_bonus_types_company_plan_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_types');
    }
};
