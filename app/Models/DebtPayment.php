<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['debt_id', 'financial_transaction_id', 'currency_code', 'principal_amount', 'interest_amount', 'fees_amount', 'principal_amount_usd', 'interest_amount_usd', 'fees_amount_usd'])]
class DebtPayment extends Model
{
    /** @return BelongsTo<Debt, $this> */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /** @return BelongsTo<FinancialTransaction, $this> */
    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:8',
            'interest_amount' => 'decimal:8',
            'fees_amount' => 'decimal:8',
            'principal_amount_usd' => 'decimal:8',
            'interest_amount_usd' => 'decimal:8',
            'fees_amount_usd' => 'decimal:8',
        ];
    }
}
