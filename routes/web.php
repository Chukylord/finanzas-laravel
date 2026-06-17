<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\RecurringController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\InvestmentAccountController;
use App\Http\Controllers\InvestmentMovementController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\InvestmentReportController;


Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('incomes', IncomeController::class)->except(['show']);
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    Route::post('/recurrings/{recurring}/generate-expense', [RecurringController::class, 'generateExpense'])
        ->name('recurrings.generateExpense');
    Route::resource('recurrings', RecurringController::class)->except(['show']);

    Route::post('/debts/{debt}/pay', [DebtController::class, 'payInstallment'])->name('debts.pay');
    Route::resource('debts', DebtController::class)->except(['show']);

    Route::resource('investments', InvestmentAccountController::class)->except(['show']);

    Route::resource('subcategories', SubcategoryController::class)->except(['show']);

    Route::get('investments/{investment}/movements', [InvestmentMovementController::class, 'index'])
        ->name('investments.movements.index');

    Route::get('investments/{investment}/movements/create', [InvestmentMovementController::class, 'create'])
        ->name('investments.movements.create');

    Route::post('investments/{investment}/movements', [InvestmentMovementController::class, 'store'])
        ->name('investments.movements.store');

    Route::get('movements/{movement}/edit', [InvestmentMovementController::class, 'edit'])
        ->name('movements.edit');

    Route::put('movements/{movement}', [InvestmentMovementController::class, 'update'])
        ->name('movements.update');

    Route::delete('movements/{movement}', [InvestmentMovementController::class, 'destroy'])
        ->name('movements.destroy');

    Route::get('/investment-reports', [InvestmentReportController::class, 'index'])
        ->name('investment-reports.index');

    Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::post('/exchange-rates', [ExchangeRateController::class, 'store'])->name('exchange-rates.store');
    Route::delete('/exchange-rates/{exchange_rate}', [ExchangeRateController::class, 'destroy'])->name('exchange-rates.destroy');

    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__.'/auth.php';
