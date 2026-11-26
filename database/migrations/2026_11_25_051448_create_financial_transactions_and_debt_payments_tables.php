<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id');
            $table->foreignId('counter_account_id')->nullable();
            $table->foreignId('budget_item_id')->nullable();
            $table->foreignId('debt_id')->nullable();
            $table->string('transaction_type', 24);
            $table->decimal('amount_original', 24, 8);
            $table->char('currency_code', 3);
            $table->decimal('exchange_rate_to_usd', 24, 12);
            $table->decimal('amount_usd', 24, 8);
            $table->decimal('account_amount', 24, 8);
            $table->char('account_currency_code', 3);
            $table->decimal('counter_account_amount', 24, 8)->nullable();
            $table->char('counter_account_currency_code', 3)->nullable();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->timestamp('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'occurred_at']);
            $table->index(['account_id', 'occurred_at']);
            $table->unique(['debt_id', 'id']);
            $table->foreign(['workspace_id', 'account_id'])
                ->references(['workspace_id', 'id'])
                ->on('accounts')
                ->restrictOnDelete();
            $table->foreign(['workspace_id', 'counter_account_id'])
                ->references(['workspace_id', 'id'])
                ->on('accounts')
                ->restrictOnDelete();
            $table->foreign(['workspace_id', 'budget_item_id'])
                ->references(['workspace_id', 'id'])
                ->on('budget_items')
                ->restrictOnDelete();
            $table->foreign(['workspace_id', 'debt_id'])
                ->references(['workspace_id', 'id'])
                ->on('debts')
                ->restrictOnDelete();
        });

        Schema::create('debt_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('debt_id');
            $table->foreignId('financial_transaction_id')->unique();
            $table->char('currency_code', 3);
            $table->decimal('principal_amount', 24, 8);
            $table->decimal('interest_amount', 24, 8)->default(0);
            $table->decimal('fees_amount', 24, 8)->default(0);
            $table->decimal('principal_amount_usd', 24, 8);
            $table->decimal('interest_amount_usd', 24, 8)->default(0);
            $table->decimal('fees_amount_usd', 24, 8)->default(0);
            $table->timestamps();
            $table->foreign('debt_id')
                ->references('id')
                ->on('debts')
                ->cascadeOnDelete();
            $table->foreign(['debt_id', 'financial_transaction_id'])
                ->references(['debt_id', 'id'])
                ->on('financial_transactions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('financial_transactions');
    }
};
