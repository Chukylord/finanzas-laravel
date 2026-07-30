<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExchangeRateController extends Controller
{
    public function index()
    {
        $userId = (int) Auth::id();
        $today = Carbon::today()->toDateString();

        $todayRate = ExchangeRate::query()
            ->forUser($userId)
            ->whereDate('date', $today)
            ->first();

        $latestRate = ExchangeRate::latestForUserOnOrBefore($userId, $today);

        $rates = ExchangeRate::query()
            ->forUser($userId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('exchange_rates.index', compact('rates', 'todayRate', 'latestRate', 'today'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'usd_ars' => ['required', 'numeric', 'gt:0', 'max:99999999.9999'],
        ]);

        $rate = ExchangeRate::query()
            ->forUser((int) Auth::id())
            ->whereDate('date', $validated['date'])
            ->first();

        if ($rate) {
            $rate->update([
                'usd_ars' => $validated['usd_ars'],
                'source' => 'manual',
            ]);
        } else {
            try {
                $rate = ExchangeRate::create([
                    'user_id' => Auth::id(),
                    'date' => $validated['date'],
                    'usd_ars' => $validated['usd_ars'],
                    'source' => 'manual',
                ]);
            } catch (UniqueConstraintViolationException) {
                $rate = ExchangeRate::query()
                    ->forUser((int) Auth::id())
                    ->whereDate('date', $validated['date'])
                    ->first();

                if (! $rate) {
                    throw ValidationException::withMessages([
                        'date' => 'No se pudo guardar una cotización para esa fecha.',
                    ]);
                }

                $rate->update([
                    'usd_ars' => $validated['usd_ars'],
                    'source' => 'manual',
                ]);
            }
        }

        $message = $rate->wasRecentlyCreated
            ? 'Tipo de cambio guardado.'
            : 'Ya existía una cotización para esa fecha y fue actualizada.';

        return redirect()->route('exchange-rates.index')->with('ok', $message);
    }

    public function edit(ExchangeRate $exchange_rate)
    {
        $this->ensureOwnership($exchange_rate);

        return view('exchange_rates.edit', ['rate' => $exchange_rate]);
    }

    public function update(Request $request, ExchangeRate $exchange_rate)
    {
        $this->ensureOwnership($exchange_rate);

        $userId = (int) Auth::id();
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'usd_ars' => ['required', 'numeric', 'gt:0', 'max:99999999.9999'],
        ]);

        $duplicateDate = ExchangeRate::query()
            ->forUser($userId)
            ->whereDate('date', $validated['date'])
            ->where('id', '!=', $exchange_rate->id)
            ->exists();

        if ($duplicateDate) {
            throw ValidationException::withMessages([
                'date' => 'Ya existe una cotización para esa fecha.',
            ]);
        }

        try {
            $exchange_rate->update([
                'date' => $validated['date'],
                'usd_ars' => $validated['usd_ars'],
                'source' => 'manual',
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'date' => 'No se pudo guardar una cotización para esa fecha.',
            ]);
        }

        return redirect()
            ->route('exchange-rates.index')
            ->with('ok', 'Tipo de cambio actualizado.');
    }

    public function destroy(ExchangeRate $exchange_rate)
    {
        $this->ensureOwnership($exchange_rate);

        $exchange_rate->delete();

        return redirect()->route('exchange-rates.index')->with('ok', 'Tipo de cambio eliminado.');
    }

    private function ensureOwnership(ExchangeRate $exchangeRate): void
    {
        abort_unless((int) $exchangeRate->user_id === (int) Auth::id(), 403);
    }
}
