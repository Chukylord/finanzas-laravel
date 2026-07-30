<?php

use App\Models\ExchangeRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access exchange rates', function () {
    $this->get(route('exchange-rates.index'))
        ->assertRedirect(route('login'));
});

test('a user can create and replace a manual rate for the same date', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('exchange-rates.store'), [
            'date' => '2026-07-24',
            'usd_ars' => '1250.5000',
        ])
        ->assertRedirect(route('exchange-rates.index'));

    $this->actingAs($user)
        ->post(route('exchange-rates.store'), [
            'date' => '2026-07-24',
            'usd_ars' => '1260.7500',
        ])
        ->assertRedirect(route('exchange-rates.index'));

    $storedRate = ExchangeRate::where('user_id', $user->id)->sole();

    expect($storedRate->date->toDateString())->toBe('2026-07-24')
        ->and((float) $storedRate->usd_ars)->toBe(1260.75)
        ->and($storedRate->source)->toBe('manual');
});

test('different users can store a rate for the same date', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    ExchangeRate::create([
        'user_id' => $firstUser->id,
        'date' => '2026-07-24',
        'usd_ars' => 1250,
        'source' => 'manual',
    ]);

    ExchangeRate::create([
        'user_id' => $secondUser->id,
        'date' => '2026-07-24',
        'usd_ars' => 1300,
        'source' => 'manual',
    ]);

    expect(ExchangeRate::whereDate('date', '2026-07-24')->count())->toBe(2);
});

test('a user cannot modify or delete another users rate', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $rate = ExchangeRate::create([
        'user_id' => $owner->id,
        'date' => '2026-07-24',
        'usd_ars' => 1250,
        'source' => 'manual',
    ]);

    $this->actingAs($otherUser)
        ->get(route('exchange-rates.edit', $rate))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->put(route('exchange-rates.update', $rate), [
            'date' => '2026-07-24',
            'usd_ars' => 1400,
        ])
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->delete(route('exchange-rates.destroy', $rate))
        ->assertForbidden();

    $this->assertDatabaseHas('exchange_rates', [
        'id' => $rate->id,
        'usd_ars' => '1250.0000',
    ]);
});

test('editing cannot create a duplicate date for the same user', function () {
    $user = User::factory()->create();
    $firstRate = ExchangeRate::create([
        'user_id' => $user->id,
        'date' => '2026-07-20',
        'usd_ars' => 1200,
        'source' => 'manual',
    ]);
    $secondRate = ExchangeRate::create([
        'user_id' => $user->id,
        'date' => '2026-07-21',
        'usd_ars' => 1250,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->from(route('exchange-rates.edit', $secondRate))
        ->put(route('exchange-rates.update', $secondRate), [
            'date' => $firstRate->date->toDateString(),
            'usd_ars' => 1300,
        ])
        ->assertRedirect(route('exchange-rates.edit', $secondRate))
        ->assertSessionHasErrors('date');

    expect($secondRate->fresh()->date->toDateString())->toBe('2026-07-21');
});

test('the applicable rate is the latest prior rate and never a future one', function () {
    $user = User::factory()->create();

    foreach ([
        ['2026-07-10', 1200],
        ['2026-07-20', 1250],
        ['2026-07-30', 1300],
    ] as [$date, $value]) {
        ExchangeRate::create([
            'user_id' => $user->id,
            'date' => $date,
            'usd_ars' => $value,
            'source' => 'manual',
        ]);
    }

    $rate = ExchangeRate::latestForUserOnOrBefore($user->id, '2026-07-24');

    expect($rate?->date->toDateString())->toBe('2026-07-20')
        ->and((float) $rate?->usd_ars)->toBe(1250.0);
});

test('the manual rate must be greater than zero', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('exchange-rates.store'), [
            'date' => '2026-07-24',
            'usd_ars' => 0,
        ])
        ->assertSessionHasErrors('usd_ars');

    $this->assertDatabaseCount('exchange_rates', 0);
});

test('investment pages use only the users latest non future rate', function () {
    Carbon::setTestNow('2026-07-24 12:00:00');

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownRate = ExchangeRate::create([
        'user_id' => $user->id,
        'date' => '2026-07-20',
        'usd_ars' => 1250,
        'source' => 'manual',
    ]);

    ExchangeRate::create([
        'user_id' => $user->id,
        'date' => '2026-07-30',
        'usd_ars' => 1300,
        'source' => 'manual',
    ]);

    ExchangeRate::create([
        'user_id' => $otherUser->id,
        'date' => '2026-07-24',
        'usd_ars' => 9999,
        'source' => 'manual',
    ]);

    $investments = $this->actingAs($user)->get(route('investments.index'));
    $investmentReport = $this->actingAs($user)->get(route('investment-reports.index', [
        'month' => 7,
        'year' => 2026,
    ]));

    $investments->assertOk();
    $investmentReport->assertOk();

    expect($investments->viewData('rate')->is($ownRate))->toBeTrue()
        ->and($investmentReport->viewData('exchangeRate')->is($ownRate))->toBeTrue();

    Carbon::setTestNow();
});
