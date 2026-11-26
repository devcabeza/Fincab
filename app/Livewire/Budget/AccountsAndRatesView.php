<?php

declare(strict_types=1);

namespace App\Livewire\Budget;

use App\Application\Budget\Actions\SyncExchangeRatesAction;
use App\Models\Account;
use App\Models\CurrencyRate;
use App\Models\FinancialTransaction;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Cuentas y Tasas | Fincab')]
class AccountsAndRatesView extends Component
{
    public ?int $selectedWorkspaceId = null;

    public string $newAccountName = '';

    public string $newAccountCurrency = 'USD';

    public string $newAccountBalance = '0.00';

    public ?int $fromAccountId = null;

    public ?int $toAccountId = null;

    public string $transferAmount = '0.00';

    public string $transferNotes = '';

    public string $transferRateSource = 'bcv';

    public string $customExchangeRate = '';

    public bool $showCreateAccountModal = false;

    public bool $showTransferModal = false;

    public bool $showEditAccountModal = false;

    public ?int $editingAccountId = null;

    public string $editAccountName = '';

    public string $editAccountCurrency = 'USD';

    public string $editAccountBalance = '0.00';

    public ?int $filterAccountId = null;

    public string $feedbackMessage = '';

    public function mount(): void
    {
        $user = Auth::user();
        if ($user) {
            $workspace = $user->workspaces()->first();
            if (! $workspace) {
                $workspace = Workspace::query()->create([
                    'owner_id' => $user->id,
                    'name' => 'Finanzas Personales',
                    'type' => 'personal',
                    'base_currency' => 'USD',
                ]);
            }

            $this->selectedWorkspaceId = $workspace->id;
        }
    }

    public function openCreateAccountModal(): void
    {
        $this->reset(['newAccountName', 'newAccountBalance']);
        $this->newAccountCurrency = 'USD';
        $this->showCreateAccountModal = true;
    }

    public function createAccount(): void
    {
        $this->validate([
            'newAccountName' => 'required|string|max:255',
            'newAccountCurrency' => 'required|string|in:USD,VES,COP',
            'newAccountBalance' => 'required|numeric',
        ], [
            'newAccountName.required' => 'El nombre de la cuenta es obligatorio.',
            'newAccountCurrency.required' => 'La moneda es obligatoria.',
            'newAccountCurrency.in' => 'La moneda debe ser USD, VES o COP.',
            'newAccountBalance.required' => 'El saldo inicial es obligatorio.',
        ]);

        if ($this->selectedWorkspaceId) {
            Account::query()->create([
                'workspace_id' => $this->selectedWorkspaceId,
                'name' => trim($this->newAccountName),
                'currency_code' => strtoupper($this->newAccountCurrency),
                'opening_balance' => (string) $this->newAccountBalance,
            ]);

            $this->reset(['newAccountName', 'newAccountBalance', 'showCreateAccountModal']);
            $this->feedbackMessage = 'Cuenta creada correctamente.';
        }
    }

    public function openEditAccountModal(int $accountId): void
    {
        /** @var Account $account */
        $account = Account::query()
            ->where('workspace_id', $this->selectedWorkspaceId)
            ->findOrFail($accountId);

        $this->editingAccountId = $account->id;
        $this->editAccountName = $account->name;
        $this->editAccountCurrency = $account->currency_code;
        $this->editAccountBalance = (string) $account->opening_balance;
        $this->showEditAccountModal = true;
    }

    public function updateAccount(): void
    {
        $this->validate([
            'editAccountName' => 'required|string|max:255',
            'editAccountCurrency' => 'required|string|in:USD,VES,COP',
            'editAccountBalance' => 'required|numeric',
        ], [
            'editAccountName.required' => 'El nombre de la cuenta es obligatorio.',
            'editAccountCurrency.required' => 'La moneda es obligatoria.',
            'editAccountCurrency.in' => 'La moneda debe ser USD, VES o COP.',
            'editAccountBalance.required' => 'El saldo vivo es obligatorio.',
        ]);

        if (! $this->selectedWorkspaceId || ! $this->editingAccountId) {
            return;
        }

        /** @var Account $account */
        $account = Account::query()
            ->where('workspace_id', $this->selectedWorkspaceId)
            ->findOrFail($this->editingAccountId);

        $account->update([
            'name' => trim($this->editAccountName),
            'currency_code' => strtoupper($this->editAccountCurrency),
            'opening_balance' => (string) $this->editAccountBalance,
        ]);

        $this->showEditAccountModal = false;
        $this->feedbackMessage = 'Cuenta actualizada correctamente.';
    }

    public function deleteAccount(int $accountId): void
    {
        if (! $this->selectedWorkspaceId) {
            return;
        }

        /** @var Account $account */
        $account = Account::query()
            ->where('workspace_id', $this->selectedWorkspaceId)
            ->findOrFail($accountId);

        $account->delete();
        $this->feedbackMessage = 'Cuenta eliminada correctamente.';
    }

    public function syncLiveRates(SyncExchangeRatesAction $syncAction): void
    {
        $syncAction->execute();
        $this->feedbackMessage = '¡Tasas de cambio sincronizadas en vivo con éxito (BCV, Paralelo, Binance y COP)!';
    }

    public function openTransferModal(): void
    {
        $this->reset(['fromAccountId', 'toAccountId', 'transferAmount', 'transferNotes', 'customExchangeRate']);
        $this->transferRateSource = 'bcv';
        $this->showTransferModal = true;
    }

    public function updatedFromAccountId(): void
    {
        $this->autoSelectRateSource();
    }

    public function updatedToAccountId(): void
    {
        $this->autoSelectRateSource();
    }

    private function autoSelectRateSource(): void
    {
        if (! $this->fromAccountId || ! $this->toAccountId || ! $this->selectedWorkspaceId) {
            return;
        }

        $fromCur = Account::query()->where('workspace_id', $this->selectedWorkspaceId)->where('id', $this->fromAccountId)->value('currency_code');
        $toCur = Account::query()->where('workspace_id', $this->selectedWorkspaceId)->where('id', $this->toAccountId)->value('currency_code');

        if (! $fromCur || ! $toCur || $fromCur === $toCur) {
            return;
        }

        if (in_array($fromCur, ['USD', 'VES'], true) && in_array($toCur, ['USD', 'VES'], true)) {
            if ($this->transferRateSource !== 'custom') {
                $this->transferRateSource = 'bcv';
            }
        } elseif (in_array($fromCur, ['USD', 'COP'], true) && in_array($toCur, ['USD', 'COP'], true)) {
            if ($this->transferRateSource !== 'custom') {
                $this->transferRateSource = 'official';
            }
        }
    }

    /**
     * @return array{
     *     isCrossCurrency: bool,
     *     fromCurrency: string,
     *     toCurrency: string,
     *     rateSource: string,
     *     quoteRate: string,
     *     destinationAmount: string,
     *     exchangeRateToUsd: string,
     *     amountUsd: string,
     *     rateLabel: string
     * }
     */
    public function getTransferConversionDetails(): array
    {
        $amount = $this->toNumeric($this->transferAmount);

        $default = [
            'isCrossCurrency' => false,
            'fromCurrency' => 'USD',
            'toCurrency' => 'USD',
            'rateSource' => $this->transferRateSource,
            'quoteRate' => '1.00000000',
            'destinationAmount' => $amount,
            'exchangeRateToUsd' => '1.000000000000',
            'amountUsd' => $amount,
            'rateLabel' => '1:1',
        ];

        if (! $this->fromAccountId || ! $this->toAccountId || ! $this->selectedWorkspaceId) {
            return $default;
        }

        /** @var Account|null $fromAccount */
        $fromAccount = Account::query()->where('workspace_id', $this->selectedWorkspaceId)->find($this->fromAccountId);
        /** @var Account|null $toAccount */
        $toAccount = Account::query()->where('workspace_id', $this->selectedWorkspaceId)->find($this->toAccountId);

        if (! $fromAccount || ! $toAccount) {
            return $default;
        }

        $fromCur = strtoupper($fromAccount->currency_code);
        $toCur = strtoupper($toAccount->currency_code);

        if ($fromCur === $toCur) {
            $multToUsd = '1.000000000000';
            if ($fromCur !== 'USD') {
                $m = CurrencyRate::getMultiplier($fromCur, 'USD', 'bcv') ?? CurrencyRate::getMultiplier($fromCur, 'USD', 'default');
                if ($m !== null && is_numeric($m)) {
                    $multToUsd = (string) $m;
                }
            }

            return [
                'isCrossCurrency' => false,
                'fromCurrency' => $fromCur,
                'toCurrency' => $toCur,
                'rateSource' => 'same',
                'quoteRate' => '1.00000000',
                'destinationAmount' => $amount,
                'exchangeRateToUsd' => $multToUsd,
                'amountUsd' => bcmul($amount, $multToUsd, 8),
                'rateLabel' => 'Misma moneda (1:1)',
            ];
        }

        // Cross-currency transfer
        $quote = '1.00';
        $label = '';
        $source = $this->transferRateSource;

        if ($source === 'custom') {
            $quote = is_numeric($this->customExchangeRate) && (float) $this->customExchangeRate > 0
                ? (string) $this->customExchangeRate
                : '1.00';
            $label = "Tasa manual: {$quote}";
        } elseif (in_array($fromCur, ['USD', 'VES'], true) && in_array($toCur, ['USD', 'VES'], true)) {
            $val = match ($source) {
                'paralelo' => CurrencyRate::getQuote('VES', 'USD', 'paralelo'),
                'binance' => CurrencyRate::getQuote('VES', 'USD', 'binance'),
                default => CurrencyRate::getQuote('VES', 'USD', 'bcv') ?? CurrencyRate::getQuote('VES', 'USD', 'default'),
            };

            $sourceTitle = match ($source) {
                'paralelo' => 'Paralelo',
                'binance' => 'Binance P2P',
                default => 'BCV Oficial',
            };

            $quote = ($val !== null && is_numeric($val) && (float) $val > 0) ? (string) $val : '1.00';
            $label = "1 USD = {$quote} VES ({$sourceTitle})";
        } elseif (in_array($fromCur, ['USD', 'COP'], true) && in_array($toCur, ['USD', 'COP'], true)) {
            $val = match ($source) {
                'binance' => CurrencyRate::getQuote('COP', 'USD', 'binance'),
                default => CurrencyRate::getQuote('COP', 'USD', 'official') ?? CurrencyRate::getQuote('COP', 'USD', 'binance'),
            };

            $sourceTitle = $source === 'binance' ? 'Binance P2P' : 'TRM Oficial';
            $quote = ($val !== null && is_numeric($val) && (float) $val > 0) ? (string) $val : '1.00';
            $label = "1 USD = {$quote} COP ({$sourceTitle})";
        } elseif (in_array($fromCur, ['VES', 'COP'], true) && in_array($toCur, ['VES', 'COP'], true)) {
            $quoteVes = CurrencyRate::getQuote('VES', 'USD', 'bcv') ?? CurrencyRate::getQuote('VES', 'USD', 'default') ?? '1.00';
            $quoteCop = CurrencyRate::getQuote('COP', 'USD', 'official') ?? CurrencyRate::getQuote('COP', 'USD', 'binance') ?? '1.00';

            if ((float) $quoteVes > 0 && (float) $quoteCop > 0) {
                if ($fromCur === 'VES') {
                    $quote = bcdiv((string) $quoteCop, (string) $quoteVes, 8);
                    $label = "1 VES ≈ {$quote} COP (Cruzada)";
                } else {
                    $quote = bcdiv((string) $quoteVes, (string) $quoteCop, 8);
                    $label = "1 COP ≈ {$quote} VES (Cruzada)";
                }
            } else {
                $quote = '1.00';
                $label = '1:1';
            }
        }

        $quoteNumeric = $this->toNumeric($quote);

        $destAmount = '0.00000000';
        $exchangeRateToUsd = '1.000000000000';
        $amountUsd = '0.00000000';

        if ($fromCur === 'USD') {
            $destAmount = bcmul($amount, $quoteNumeric, 8);
            $exchangeRateToUsd = '1.000000000000';
            $amountUsd = $amount;
        } elseif ($toCur === 'USD') {
            $destAmount = (float) $quoteNumeric > 0 ? bcdiv($amount, $quoteNumeric, 8) : $amount;
            $exchangeRateToUsd = (float) $quoteNumeric > 0 ? bcdiv('1', $quoteNumeric, 12) : '1.000000000000';
            $amountUsd = $destAmount;
        } elseif ($fromCur === 'VES' && $toCur === 'COP') {
            $destAmount = bcmul($amount, $quoteNumeric, 8);
            $mult = CurrencyRate::getMultiplier('VES', 'USD', 'bcv');
            $exchangeRateToUsd = $this->toNumeric($mult ?? '1.00');
            $amountUsd = bcmul($amount, $exchangeRateToUsd, 8);
        } elseif ($fromCur === 'COP' && $toCur === 'VES') {
            $destAmount = bcmul($amount, $quoteNumeric, 8);
            $mult = CurrencyRate::getMultiplier('COP', 'USD', 'official');
            $exchangeRateToUsd = $this->toNumeric($mult ?? '1.00');
            $amountUsd = bcmul($amount, $exchangeRateToUsd, 8);
        }

        return [
            'isCrossCurrency' => true,
            'fromCurrency' => $fromCur,
            'toCurrency' => $toCur,
            'rateSource' => $source,
            'quoteRate' => $quote,
            'destinationAmount' => $destAmount,
            'exchangeRateToUsd' => $exchangeRateToUsd,
            'amountUsd' => $amountUsd,
            'rateLabel' => $label,
        ];
    }

    public function executeTransfer(): void
    {
        $conversion = $this->getTransferConversionDetails();

        $validationRules = [
            'fromAccountId' => 'required|integer',
            'toAccountId' => 'required|integer|different:fromAccountId',
            'transferAmount' => 'required|numeric|gt:0',
        ];

        if ($conversion['isCrossCurrency'] && $this->transferRateSource === 'custom') {
            $validationRules['customExchangeRate'] = 'required|numeric|gt:0';
        }

        $this->validate($validationRules, [
            'fromAccountId.required' => 'La cuenta de origen es obligatoria.',
            'toAccountId.required' => 'La cuenta de destino es obligatoria.',
            'toAccountId.different' => 'La cuenta de destino debe ser distinta a la de origen.',
            'transferAmount.required' => 'El monto a transferir debe ser mayor a 0.',
            'customExchangeRate.required' => 'La tasa personalizada es obligatoria para transferencias entre diferentes monedas.',
            'customExchangeRate.gt' => 'La tasa personalizada debe ser mayor a 0.',
        ]);

        if (! $this->selectedWorkspaceId) {
            return;
        }

        DB::transaction(function () use ($conversion): void {
            /** @var Account $fromAccount */
            $fromAccount = Account::query()
                ->where('workspace_id', $this->selectedWorkspaceId)
                ->findOrFail($this->fromAccountId);

            /** @var Account $toAccount */
            $toAccount = Account::query()
                ->where('workspace_id', $this->selectedWorkspaceId)
                ->findOrFail($this->toAccountId);

            $amount = $this->toNumeric($this->transferAmount);
            $destinationAmount = $this->toNumeric($conversion['destinationAmount']);

            $newFromBalance = bcsub($this->toNumeric($fromAccount->opening_balance), $amount, 8);
            $newToBalance = bcadd($this->toNumeric($toAccount->opening_balance), $destinationAmount, 8);

            $fromAccount->update(['opening_balance' => $newFromBalance]);
            $toAccount->update(['opening_balance' => $newToBalance]);

            $notes = trim($this->transferNotes);
            if ($conversion['isCrossCurrency']) {
                $conversionNote = "Tasa: {$conversion['rateLabel']} → Acreditado: {$destinationAmount} {$toAccount->currency_code}";
                $notes = $notes !== '' ? "{$notes} ({$conversionNote})" : $conversionNote;
            }

            FinancialTransaction::query()->create([
                'workspace_id' => $this->selectedWorkspaceId,
                'account_id' => $fromAccount->id,
                'counter_account_id' => $toAccount->id,
                'transaction_type' => 'transfer',
                'amount_original' => $amount,
                'currency_code' => $fromAccount->currency_code,
                'exchange_rate_to_usd' => $conversion['exchangeRateToUsd'],
                'amount_usd' => $conversion['amountUsd'],
                'account_amount' => $amount,
                'account_currency_code' => $fromAccount->currency_code,
                'counter_account_amount' => $destinationAmount,
                'counter_account_currency_code' => $toAccount->currency_code,
                'occurred_at' => now(),
                'notes' => $notes ?: 'Transferencia interna',
            ]);
        });

        $this->reset(['fromAccountId', 'toAccountId', 'transferAmount', 'transferNotes', 'customExchangeRate', 'showTransferModal']);
        $this->feedbackMessage = 'Transferencia realizada con éxito.';
    }

    public function render(): View
    {
        $accounts = $this->selectedWorkspaceId
            ? Account::query()->where('workspace_id', $this->selectedWorkspaceId)->get()
            : collect();

        $rates = CurrencyRate::query()->latest('effective_on')->get();

        $txQuery = FinancialTransaction::query()
            ->where('workspace_id', $this->selectedWorkspaceId)
            ->with(['account', 'counterAccount', 'budgetItem']);

        if ($this->filterAccountId) {
            $txQuery->where(function ($q) {
                $q->where('account_id', $this->filterAccountId)
                    ->orWhere('counter_account_id', $this->filterAccountId);
            });
        }

        $recentTransactions = $this->selectedWorkspaceId
            ? $txQuery->latest('occurred_at')->take(25)->get()
            : collect();

        $latestBcv = CurrencyRate::getQuote('VES', 'USD', 'bcv');
        $latestParalelo = CurrencyRate::getQuote('VES', 'USD', 'paralelo');
        $latestBinanceVes = CurrencyRate::getQuote('VES', 'USD', 'binance');
        $latestCop = CurrencyRate::getQuote('COP', 'USD', 'official') ?? CurrencyRate::getQuote('COP', 'USD', 'binance');

        return view('livewire.budget.accounts-and-rates-view', [
            'accounts' => $accounts,
            'rates' => $rates,
            'recentTransactions' => $recentTransactions,
            'latestBcv' => $latestBcv,
            'latestParalelo' => $latestParalelo,
            'latestBinanceVes' => $latestBinanceVes,
            'latestCop' => $latestCop,
        ]);
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
