<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the main authenticated pages render', function (string $routeName) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk();
})->with([
    'dashboard' => 'dashboard',
    'incomes' => 'incomes.index',
    'expenses' => 'expenses.index',
    'categories' => 'categories.index',
    'subcategories' => 'subcategories.index',
    'recurrings' => 'recurrings.index',
    'debts' => 'debts.index',
    'investments' => 'investments.index',
    'investment reports' => 'investment-reports.index',
    'exchange rates' => 'exchange-rates.index',
    'reports' => 'reports.index',
    'backups' => 'backups.index',
]);

test('the csv export uses the authenticated report route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('reports.export', [
            'quick' => 'current_month',
            'movement_type' => 'all',
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('quick report filters replace stale dates', function () {
    Carbon::setTestNow('2026-07-24 12:00:00');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.index', [
        'quick' => 'current_month',
        'date_from' => '2020-01-01',
        'date_to' => '2020-01-31',
    ]));

    $response->assertOk();

    expect($response->viewData('filters')['from'])->toBe('2026-07-01')
        ->and($response->viewData('filters')['to'])->toBe('2026-07-31');

    Carbon::setTestNow();
});

test('the csv export respects all filters and user ownership', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $incomeCategory = Category::create([
        'user_id' => $user->id,
        'name' => 'Salary',
        'type' => 'income',
    ]);
    $expenseCategory = Category::create([
        'user_id' => $user->id,
        'name' => 'Food',
        'type' => 'expense',
    ]);
    $otherCategory = Category::create([
        'user_id' => $otherUser->id,
        'name' => 'Other',
        'type' => 'income',
    ]);

    Income::create([
        'user_id' => $user->id,
        'category_id' => $incomeCategory->id,
        'date' => '2026-07-10',
        'amount' => 100,
        'description' => 'INCLUDED_ROW',
        'method' => 'bank',
    ]);
    Income::create([
        'user_id' => $user->id,
        'category_id' => $incomeCategory->id,
        'date' => '2026-06-10',
        'amount' => 200,
        'description' => 'OUTSIDE_DATE',
        'method' => 'bank',
    ]);
    Expense::create([
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'date' => '2026-07-10',
        'amount' => 50,
        'description' => 'WRONG_TYPE',
        'method' => 'bank',
    ]);
    Income::create([
        'user_id' => $otherUser->id,
        'category_id' => $otherCategory->id,
        'date' => '2026-07-10',
        'amount' => 999,
        'description' => 'OTHER_USER',
        'method' => 'bank',
    ]);

    $response = $this->actingAs($user)->get(route('reports.export', [
        'date_from' => '2026-07-01',
        'date_to' => '2026-07-31',
        'movement_type' => 'income',
        'category_id' => $incomeCategory->id,
        'method' => 'bank',
    ]));

    $content = $response->streamedContent();

    $response->assertOk();
    expect($content)->toContain('INCLUDED_ROW')
        ->not->toContain('OUTSIDE_DATE')
        ->not->toContain('WRONG_TYPE')
        ->not->toContain('OTHER_USER');
});
