<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_runs', function (Blueprint $table) {
            $table->decimal('total_bonus_amount', 14, 2)->default(0)->after('total_viral_commission');
        });
    }

    public function down(): void
    {
        Schema::table('commission_runs', function (Blueprint $table) {
            $table->dropColumn('total_bonus_amount');
        });
    }
};
