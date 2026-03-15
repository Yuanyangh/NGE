<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genealogy_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('user_id')->unique()->constrained();
            $table->unsignedBigInteger('sponsor_id')->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->unsignedInteger('tree_depth')->default(0);
            $table->timestamps();

            $table->foreign('sponsor_id')->references('id')->on('genealogy_nodes');
            $table->index(['company_id', 'sponsor_id'], 'idx_genealogy_company_sponsor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genealogy_nodes');
    }
};
