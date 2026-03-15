<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('user_id')->unique()->constrained();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
        });

        Schema::create('wallet_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('wallet_account_id')->constrained();
            $table->enum('type', ['commission_credit', 'commission_release', 'withdrawal', 'clawback', 'hold', 'adjustment']);
            $table->decimal('amount', 12, 4);
            $table->enum('status', ['pending', 'approved', 'released', 'held', 'reversed'])->default('pending');
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->timestamp('effective_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['wallet_account_id', 'status', 'effective_at'], 'idx_wallet_movements_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_movements');
        Schema::dropIfExists('wallet_accounts');
    }
};
