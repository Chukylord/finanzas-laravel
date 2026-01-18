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
});

require __DIR__.'/auth.php';
