<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Account;
use App\Models\BudgetItem;
use App\Models\CurrencyRate;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;

final class BudgetItemObserver
{
    public function created(BudgetItem $item): void
    {
        if ($item->status === 'paid') {
            if ($item->debt_id !== null) {
                $this->applyDebtAmortization($item);
            } else {
                $this->applyGeneralTransaction($item);
            }
        }
    }

    public function updated(BudgetItem $item): void
    {
        if (! $item->isDirty('status')) {
            return;
        }

        $newStatus = (string) $item->status;
        $originalStatus = (string) $item->getOriginal('status');

        if ($newStatus === 'paid' && $originalStatus !== 'paid') {
            if ($item->debt_id !== null) {
                $this->applyDebtAmortization($item);
            } else {
                $this->applyGeneralTransaction($item);
            }
        } elseif ($newStatus === 'pending' && $originalStatus === 'paid') {
            if ($item->debt_id !== null) {
                $this->reverseDebtAmortization($item);
            } else {
                $this->reverseGeneralTransaction($item);
            }
        }
    }

    private function applyDebtAmortization(BudgetItem $item): void
    {
        DB::transaction(function () use ($item): void {
            if ($item->debt_id === null) {
                return;
            }

            /** @var Debt $debt */
            $debt = Debt::query()->lockForUpdate()->findOrFail($item->debt_id);

            $idempotencyKey = "budget-item-payment-{$item->id}";
            $existingTransaction = FinancialTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingTransaction !== null) {
                return;
            }

            $amountQ1 = $this->toNumeric($item->amount_q1);
            $amountQ2 = $this->toNumeric($item->amount_q2);
            $paymentAmount = bcadd($amountQ1, $amountQ2, 8);

            if (bccomp($paymentAmount, '0', 8) <= 0) {
                return;
            }

            $currentBalance = $this->toNumeric($debt->current_balance);
            $newBalance = bcsub($currentBalance, $paymentAmount, 8);
            if (bccomp($newBalance, '0', 8) < 0) {
                $newBalance = '0.00000000';
            }

            $debt->update([
                'current_balance' => $newBalance,
            ]);

            /** @var Account|null $account */
            $account = null;
            if ($item->account_id !== null) {
                $account = Account::query()
                    ->where('workspace_id', $item->workspace_id)
                    ->find($item->account_id);
            }

            if ($account === null) {
                $account = Account::query()
                    ->where('workspace_id', $item->workspace_id)
                    ->where('currency_code', $debt->currency_code)
                    ->first()
                    ?? Account::query()->where('workspace_id', $item->workspace_id)->first()
                    ?? Account::query()->create([
                        'workspace_id' => $item->workspace_id,
                        'name' => 'Default Account',
                        'currency_code' => $debt->currency_code,
                        'opening_balance' => '0.00000000',
                    ]);
            }

            $exchangeRate = '1.000000000000';
            if ($debt->currency_code !== 'USD') {
                $rate = CurrencyRate::getMultiplier($debt->currency_code, 'USD');
                if ($rate !== null && is_numeric($rate) && (float) $rate > 0) {
                    $exchangeRate = (string) $rate;
                }
            }

            $paymentAmountUsd = bcmul($paymentAmount, $exchangeRate, 8);

            $accountDeduction = $paymentAmount;
            if ($account->currency_code !== $debt->currency_code) {
                if ($account->currency_code === 'USD') {
                    $accountDeduction = $paymentAmountUsd;
                } else {
                    $accMult = CurrencyRate::getMultiplier($account->currency_code, 'USD');
                    $accountDeduction = ($accMult && (float) $accMult > 0)
                        ? bcdiv($paymentAmountUsd, (string) $accMult, 8)
                        : $paymentAmount;
                }
            }

            /** @var FinancialTransaction $transaction */
            $transaction = FinancialTransaction::query()->create([
                'workspace_id' => $item->workspace_id,
                'account_id' => $account->id,
                'budget_item_id' => $item->id,
                'debt_id' => $debt->id,
                'transaction_type' => 'debt_payment',
                'amount_original' => $paymentAmount,
                'currency_code' => $debt->currency_code,
                'exchange_rate_to_usd' => $exchangeRate,
                'amount_usd' => $paymentAmountUsd,
                'account_amount' => $accountDeduction,
                'account_currency_code' => $account->currency_code,
                'idempotency_key' => $idempotencyKey,
                'occurred_at' => now(),
            ]);

            $currentAccountBal = $this->toNumeric($account->opening_balance);
            $newAccountBal = bcsub($currentAccountBal, $accountDeduction, 8);
            $account->update(['opening_balance' => $newAccountBal]);

            DebtPayment::query()->create([
                'debt_id' => $debt->id,
                'financial_transaction_id' => $transaction->id,
                'currency_code' => $debt->currency_code,
                'principal_amount' => $paymentAmount,
                'interest_amount' => '0.00000000',
                'fees_amount' => '0.00000000',
                'principal_amount_usd' => $paymentAmountUsd,
                'interest_amount_usd' => '0.00000000',
                'fees_amount_usd' => '0.00000000',
            ]);
        });
    }

    private function reverseDebtAmortization(BudgetItem $item): void
    {
        DB::transaction(function () use ($item): void {
            if ($item->debt_id === null) {
                return;
            }

            /** @var Debt $debt */
            $debt = Debt::query()->lockForUpdate()->findOrFail($item->debt_id);

            $idempotencyKey = "budget-item-payment-{$item->id}";
            $transaction = FinancialTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($transaction === null) {
                return;
            }

            $payment = DebtPayment::query()
                ->where('financial_transaction_id', $transaction->id)
                ->first();

            if ($payment !== null) {
                $principal = $this->toNumeric($payment->principal_amount);
                $currentBalance = $this->toNumeric($debt->current_balance);
                $restoredBalance = bcadd($currentBalance, $principal, 8);

                $debt->update([
                    'current_balance' => $restoredBalance,
                ]);

                /** @var Account|null $account */
                $account = Account::query()->find($transaction->account_id);
                if ($account !== null) {
                    $currentAccountBal = $this->toNumeric($account->opening_balance);
                    $restoredAccountBal = bcadd($currentAccountBal, $this->toNumeric($transaction->account_amount), 8);
                    $account->update(['opening_balance' => $restoredAccountBal]);
                }

                $payment->delete();
            }

            $transaction->delete();
        });
    }

    private function applyGeneralTransaction(BudgetItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $amountQ1 = $this->toNumeric($item->amount_q1);
            $amountQ2 = $this->toNumeric($item->amount_q2);
            $totalAmount = bcadd($amountQ1, $amountQ2, 8);

            if (bccomp($totalAmount, '0', 8) <= 0) {
                return;
            }

            $idempotencyKey = "budget-item-paid-{$item->id}";
            $existing = FinancialTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return;
            }

            /** @var Account|null $account */
            $account = null;
            if ($item->account_id !== null) {
                $account = Account::query()
                    ->where('workspace_id', $item->workspace_id)
                    ->find($item->account_id);
            }

            if ($account === null) {
                $account = Account::query()
                    ->where('workspace_id', $item->workspace_id)
                    ->first();
            }

            if ($account === null) {
                return;
            }

            $isIncome = $item->item_type === 'income';
            $transactionType = $isIncome ? 'income' : 'expense';

            $exchangeRate = '1.000000000000';
            $accountAmount = $totalAmount;
            $amountUsd = $totalAmount;

            if ($account->currency_code !== 'USD') {
                $multiplier = CurrencyRate::getMultiplier($account->currency_code, 'USD');
                if ($multiplier !== null && is_numeric($multiplier) && (float) $multiplier > 0) {
                    $exchangeRate = (string) $multiplier;
                    $accountAmount = bcdiv($totalAmount, (string) $multiplier, 8);
                }
            }

            $currentOpening = $this->toNumeric($account->opening_balance);
            $newOpening = $isIncome
                ? bcadd($currentOpening, $accountAmount, 8)
                : bcsub($currentOpening, $accountAmount, 8);

            $account->update(['opening_balance' => $newOpening]);

            FinancialTransaction::query()->create([
                'workspace_id' => $item->workspace_id,
                'account_id' => $account->id,
                'budget_item_id' => $item->id,
                'transaction_type' => $transactionType,
                'amount_original' => $accountAmount,
                'currency_code' => $account->currency_code,
                'exchange_rate_to_usd' => $exchangeRate,
                'amount_usd' => $amountUsd,
                'account_amount' => $accountAmount,
                'account_currency_code' => $account->currency_code,
                'idempotency_key' => $idempotencyKey,
                'occurred_at' => now(),
                'notes' => $isIncome ? "Ingreso presupuestado: {$item->title}" : "Pago presupuestado: {$item->title}",
            ]);
        });
    }

    private function reverseGeneralTransaction(BudgetItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $idempotencyKey = "budget-item-paid-{$item->id}";
            /** @var FinancialTransaction|null $transaction */
            $transaction = FinancialTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($transaction === null) {
                return;
            }

            /** @var Account|null $account */
            $account = Account::query()->find($transaction->account_id);
            if ($account !== null) {
                $amount = $this->toNumeric($transaction->account_amount);
                $currentOpening = $this->toNumeric($account->opening_balance);
                $newOpening = $transaction->transaction_type === 'income'
                    ? bcsub($currentOpening, $amount, 8)
                    : bcadd($currentOpening, $amount, 8);

                $account->update(['opening_balance' => $newOpening]);
            }

            $transaction->delete();
        });
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
