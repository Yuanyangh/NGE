<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->unsignedBigInteger('referred_by_user_id')->nullable();
            $table->enum('type', ['purchase', 'smartship', 'refund', 'adjustment']);
            $table->decimal('amount', 12, 2);
            $table->decimal('xp', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'confirmed', 'reversed'])->default('confirmed');
            $table->boolean('qualifies_for_commission')->default(true);
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->foreign('referred_by_user_id')->references('id')->on('users');
            $table->index(['company_id', 'transaction_date'], 'idx_transactions_company_date');
            $table->index(['user_id', 'transaction_date'], 'idx_transactions_user_date');
            $table->index(['referred_by_user_id', 'transaction_date'], 'idx_transactions_referred_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
