<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('compensation_plan_id')->constrained();
            $table->date('run_date');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->decimal('total_affiliate_commission', 14, 2)->default(0);
            $table->decimal('total_viral_commission', 14, 2)->default(0);
            $table->decimal('total_company_volume', 14, 2)->default(0);
            $table->boolean('viral_cap_triggered')->default(false);
            $table->decimal('viral_cap_reduction_pct', 8, 4)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'run_date'], 'idx_runs_company_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_runs');
    }
};
