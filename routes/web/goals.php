<?php

use App\Livewire\Goals\GoalsDashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Financial Goals & Targets Feature Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function (): void {
    Route::get('/goals', GoalsDashboard::class)->name('goals.index');
});
