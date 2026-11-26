<?php

declare(strict_types=1);

use App\NativeComponents\MobileDashboardScreen;
use App\NativeComponents\MobileDebtScreen;
use App\NativeComponents\QuickExpenseScreen;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    Route::native('/', MobileDashboardScreen::class)->name('mobile.dashboard');
    Route::native('/quick-entry', QuickExpenseScreen::class)->name('mobile.quick-entry');
    Route::native('/debts', MobileDebtScreen::class)->name('mobile.debts');
});
