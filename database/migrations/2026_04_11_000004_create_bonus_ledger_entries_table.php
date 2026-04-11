<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('commission_run_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('bonus_type_id')->constrained('bonus_types');
            $table->decimal('amount', 12, 4);
            $table->unsignedInteger('tier_achieved')->nullable();
            $table->json('qualification_snapshot')->nullable();
            $table->string('description', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('commission_run_id', 'idx_bonus_ledger_run');
            $table->index(['user_id', 'created_at'], 'idx_bonus_ledger_user_date');
            $table->index('bonus_type_id', 'idx_bonus_ledger_bonus_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_ledger_entries');
    }
};
