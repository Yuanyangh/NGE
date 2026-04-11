<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_type_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bonus_type_id')->constrained('bonus_types')->cascadeOnDelete();
            $table->string('key', 100);
            $table->text('value');

            $table->unique(['bonus_type_id', 'key'], 'uq_bonus_type_configs_bonus_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_type_configs');
    }
};
