<?php

use App\Livewire\Budget\AccountsAndRatesView;
use App\Livewire\Budget\BudgetSheetView;
use App\Livewire\Budget\DashboardOverview;
use App\Livewire\Budget\DebtManagerView;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard & Budget Feature Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardOverview::class)->name('dashboard');
    Route::get('/budget', BudgetSheetView::class)->name('budget.sheet');
    Route::get('/debts', DebtManagerView::class)->name('budget.debts');
    Route::get('/accounts', AccountsAndRatesView::class)->name('budget.accounts');
});
