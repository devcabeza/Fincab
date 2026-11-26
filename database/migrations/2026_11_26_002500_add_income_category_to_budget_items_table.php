<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_items', function (Blueprint $table): void {
            $table->string('income_category', 32)->nullable()->after('item_type');
            $table->index(['budget_period_id', 'income_category']);
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table): void {
            $table->dropIndex(['budget_period_id', 'income_category']);
            $table->dropColumn('income_category');
        });
    }
};
