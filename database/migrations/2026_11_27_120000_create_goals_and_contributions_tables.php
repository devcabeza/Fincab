<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 32)->default('target_amount');
            $table->string('status', 32)->default('active');
            $table->char('currency_code', 3)->default('USD');
            $table->decimal('target_amount', 24, 8)->default(0);
            $table->decimal('current_amount', 24, 8)->default(0);
            $table->decimal('monthly_target', 24, 8)->default(0);
            $table->decimal('planned_monthly_quota', 24, 8)->default(0);
            $table->date('start_date');
            $table->date('target_date')->nullable();
            $table->date('projected_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workspace_id', 'is_active']);
            $table->index(['workspace_id', 'type']);
        });

        Schema::create('goal_contributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $table->decimal('amount', 24, 8);
            $table->char('currency_code', 3)->default('USD');
            $table->timestamp('occurred_at');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['goal_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_contributions');
        Schema::dropIfExists('goals');
    }
};
