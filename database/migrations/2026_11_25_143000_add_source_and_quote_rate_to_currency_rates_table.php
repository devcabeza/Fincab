<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currency_rates', function (Blueprint $table): void {
            $table->string('source', 32)->default('default')->after('to_currency');
            $table->decimal('quote_rate', 24, 8)->nullable()->after('rate');
            $table->dropUnique(['from_currency', 'to_currency', 'effective_on']);
            $table->unique(['from_currency', 'to_currency', 'source', 'effective_on']);
        });
    }

    public function down(): void
    {
        Schema::table('currency_rates', function (Blueprint $table): void {
            $table->dropUnique(['from_currency', 'to_currency', 'source', 'effective_on']);
            $table->unique(['from_currency', 'to_currency', 'effective_on']);
            $table->dropColumn(['source', 'quote_rate']);
        });
    }
};
