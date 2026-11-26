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
            'newAccountCurrency' => 'required|string|size:3',
            'newAccountBalance' => 'required|numeric',
        ], [
            'newAccountName.required' => 'El nombre de la cuenta es obligatorio.',
            'newAccountCurrency.required' => 'La moneda es obligatoria.',
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
            'editAccountCurrency' => 'required|string|size:3',
            'editAccountBalance' => 'required|numeric',
        ], [
            'editAccountName.required' => 'El nombre de la cuenta es obligatorio.',
            'editAccountCurrency.required' => 'La moneda es obligatoria.',
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

    public function executeTransfer(): void
    {
        $this->validate([
            'fromAccountId' => 'required|integer',
            'toAccountId' => 'required|integer|different:fromAccountId',
            'transferAmount' => 'required|numeric|gt:0',
        ], [
            'fromAccountId.required' => 'La cuenta de origen es obligatoria.',
            'toAccountId.required' => 'La cuenta de destino es obligatoria.',
            'toAccountId.different' => 'La cuenta de destino debe ser distinta a la de origen.',
            'transferAmount.required' => 'El monto a transferir debe ser mayor a 0.',
        ]);

        if (! $this->selectedWorkspaceId) {
            return;
        }

        DB::transaction(function (): void {
            /** @var Account $fromAccount */
            $fromAccount = Account::query()
                ->where('workspace_id', $this->selectedWorkspaceId)
                ->findOrFail($this->fromAccountId);

            /** @var Account $toAccount */
            $toAccount = Account::query()
                ->where('workspace_id', $this->selectedWorkspaceId)
                ->findOrFail($this->toAccountId);

            $amount = $this->toNumeric($this->transferAmount);

            $newFromBalance = bcsub($this->toNumeric($fromAccount->opening_balance), $amount, 8);
            $newToBalance = bcadd($this->toNumeric($toAccount->opening_balance), $amount, 8);

            $fromAccount->update(['opening_balance' => $newFromBalance]);
            $toAccount->update(['opening_balance' => $newToBalance]);

            FinancialTransaction::query()->create([
                'workspace_id' => $this->selectedWorkspaceId,
                'account_id' => $fromAccount->id,
                'counter_account_id' => $toAccount->id,
                'transaction_type' => 'transfer',
                'amount_original' => $amount,
                'currency_code' => $fromAccount->currency_code,
                'exchange_rate_to_usd' => '1.000000000000',
                'amount_usd' => $amount,
                'account_amount' => $amount,
                'account_currency_code' => $fromAccount->currency_code,
                'counter_account_amount' => $amount,
                'counter_account_currency_code' => $toAccount->currency_code,
                'occurred_at' => now(),
                'notes' => trim($this->transferNotes) ?: 'Transferencia interna',
            ]);
        });

        $this->reset(['fromAccountId', 'toAccountId', 'transferAmount', 'transferNotes', 'showTransferModal']);
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
