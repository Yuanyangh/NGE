<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_ledger_entries', function (Blueprint $table) {
            $table->index(['company_id', 'user_id', 'created_at'], 'idx_ledger_company_user_date');
            $table->index(['company_id', 'type'], 'idx_ledger_company_type');
        });

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->index('company_id', 'idx_wallet_movements_company');
        });
    }

    public function down(): void
    {
        Schema::table('commission_ledger_entries', function (Blueprint $table) {
            $table->dropIndex('idx_ledger_company_user_date');
            $table->dropIndex('idx_ledger_company_type');
        });

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropIndex('idx_wallet_movements_company');
        });
    }
};
