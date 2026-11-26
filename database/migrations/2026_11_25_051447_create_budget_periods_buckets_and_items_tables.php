<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->char('base_currency', 3)->default('USD');
            $table->string('debt_strategy', 32)->default('avalanche');
            $table->decimal('extra_debt_payment_percent', 5, 2)->default(20);
            $table->timestamps();

            $table->unique(['workspace_id', 'period_start']);
            $table->unique(['workspace_id', 'id']);
        });

        Schema::create('budget_buckets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_period_id');
            $table->string('key', 64);
            $table->string('name');
            $table->decimal('allocation_percent', 5, 2)->default(0);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();

            $table->unique(['budget_period_id', 'key']);
            $table->unique(['workspace_id', 'id']);
            $table->foreign(['workspace_id', 'budget_period_id'])
                ->references(['workspace_id', 'id'])
                ->on('budget_periods')
                ->cascadeOnDelete();
        });

        Schema::create('budget_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_period_id');
            $table->foreignId('budget_bucket_id')->nullable();
            $table->foreignId('debt_id')->nullable();
            $table->string('title');
            $table->string('item_type', 16);
            $table->string('split_mode', 16)->default('manual');
            $table->decimal('amount_q1', 24, 8)->default(0);
            $table->decimal('amount_q2', 24, 8)->default(0);
            $table->string('status', 16)->default('pending');
            $table->boolean('is_recurring')->default(false);
            $table->boolean('minimum_payment_protected')->default(false);
            $table->timestamps();

            $table->index(['budget_period_id', 'status']);
            $table->unique(['workspace_id', 'id']);
            $table->foreign(['workspace_id', 'budget_period_id'])
                ->references(['workspace_id', 'id'])
                ->on('budget_periods')
                ->cascadeOnDelete();
            $table->foreign(['workspace_id', 'budget_bucket_id'])
                ->references(['workspace_id', 'id'])
                ->on('budget_buckets')
                ->restrictOnDelete();
            $table->foreign(['workspace_id', 'debt_id'])
                ->references(['workspace_id', 'id'])
                ->on('debts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('budget_buckets');
        Schema::dropIfExists('budget_periods');
    }
};
