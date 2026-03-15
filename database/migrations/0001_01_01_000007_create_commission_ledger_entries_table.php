<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('commission_run_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['affiliate_commission', 'viral_commission', 'cap_adjustment', 'manual_adjustment']);
            $table->decimal('amount', 12, 4);
            $table->unsignedInteger('tier_achieved')->nullable();
            $table->json('qualification_snapshot')->nullable();
            $table->string('description', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('commission_run_id', 'idx_ledger_run');
            $table->index(['user_id', 'type', 'created_at'], 'idx_ledger_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_ledger_entries');
    }
};
