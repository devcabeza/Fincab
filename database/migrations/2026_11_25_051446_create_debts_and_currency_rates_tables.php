<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('creditor')->nullable();
            $table->char('currency_code', 3)->default('USD');
            $table->decimal('initial_balance', 24, 8);
            $table->decimal('current_balance', 24, 8);
            $table->decimal('annual_interest_rate', 12, 8)->default(0);
            $table->decimal('minimum_payment', 24, 8)->default(0);
            $table->string('payment_frequency', 32)->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workspace_id', 'is_active']);
            $table->unique(['workspace_id', 'id']);
        });

        Schema::create('currency_rates', function (Blueprint $table): void {
            $table->id();
            $table->char('from_currency', 3);
            $table->char('to_currency', 3);
            $table->decimal('rate', 24, 12);
            $table->date('effective_on');
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
        Schema::dropIfExists('debts');
    }
};
