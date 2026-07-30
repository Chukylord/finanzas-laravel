<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\InvestmentAccount;
use App\Models\InvestmentMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InvestmentReportController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('investment_accounts', 'id')
                    ->where(fn ($query) => $query->where('user_id', $userId)),
            ],
        ]);

        $month = (int) ($validated['month'] ?? now()->month);
        $year = (int) ($validated['year'] ?? now()->year);
        $accountId = $validated['account_id'] ?? null;

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $rateCutoff = Carbon::parse($end)->min(Carbon::today())->toDateString();
        $exchangeRate = ExchangeRate::latestForUserOnOrBefore((int) $userId, $rateCutoff);
        $usdArs = $exchangeRate ? (float) $exchangeRate->usd_ars : null;

        $accounts = InvestmentAccount::where('user_id', $userId)
            ->orderBy('name')
            ->get();

        $accountIds = $accounts->pluck('id')->toArray();

        $movements = InvestmentMovement::with('account')
            ->whereIn('investment_account_id', $accountIds)
            ->whereBetween('date', [$start, $end])
            ->when($accountId, function ($query) use ($accountId) {
                $query->where('investment_account_id', $accountId);
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        /*
            Criterio:
            - deposit, profit, adjust => suman
            - withdraw, loss, fee => restan

            USD comprados:
            - movimientos tipo deposit en USD
        */

        $positiveTypes = ['deposit', 'profit', 'adjust'];
        $negativeTypes = ['withdraw', 'loss', 'fee'];

        $summary = [
            'ars' => [
                'deposits' => 0,
                'withdraws' => 0,
                'profits' => 0,
                'losses' => 0,
                'fees' => 0,
                'adjusts' => 0,
                'net' => 0,
            ],
            'usd' => [
                'deposits' => 0,
                'withdraws' => 0,
                'profits' => 0,
                'losses' => 0,
                'fees' => 0,
                'adjusts' => 0,
                'net' => 0,
            ],
        ];

        foreach ($movements as $m) {
            $currency = strtolower($m->currency) === 'usd' ? 'usd' : 'ars';
            $amount = (float) $m->amount;

            if ($m->type === 'deposit') {
                $summary[$currency]['deposits'] += $amount;
            }

            if ($m->type === 'withdraw') {
                $summary[$currency]['withdraws'] += $amount;
            }

            if ($m->type === 'profit') {
                $summary[$currency]['profits'] += $amount;
            }

            if ($m->type === 'loss') {
                $summary[$currency]['losses'] += $amount;
            }

            if ($m->type === 'fee') {
                $summary[$currency]['fees'] += $amount;
            }

            if ($m->type === 'adjust') {
                $summary[$currency]['adjusts'] += $amount;
            }

            if (in_array($m->type, $positiveTypes)) {
                $summary[$currency]['net'] += $amount;
            }

            if (in_array($m->type, $negativeTypes)) {
                $summary[$currency]['net'] -= $amount;
            }
        }

        $usdBought = $summary['usd']['deposits'];
        $usdNetArs = $usdArs !== null ? $summary['usd']['net'] * $usdArs : null;
        $combinedNetArs = $usdNetArs !== null ? $summary['ars']['net'] + $usdNetArs : null;

        $byAccount = $movements
            ->groupBy('investment_account_id')
            ->map(function ($items) use ($positiveTypes, $negativeTypes, $usdArs) {
                $account = $items->first()->account;

                $arsNet = 0;
                $usdNet = 0;
                $usdBought = 0;

                foreach ($items as $m) {
                    $amount = (float) $m->amount;
                    $currency = strtoupper($m->currency);

                    $signed = 0;

                    if (in_array($m->type, $positiveTypes)) {
                        $signed = $amount;
                    }

                    if (in_array($m->type, $negativeTypes)) {
                        $signed = -$amount;
                    }

                    if ($currency === 'USD') {
                        $usdNet += $signed;

                        if ($m->type === 'deposit') {
                            $usdBought += $amount;
                        }
                    } else {
                        $arsNet += $signed;
                    }
                }

                return [
                    'account' => $account,
                    'ars_net' => $arsNet,
                    'usd_net' => $usdNet,
                    'ars_equiv' => $usdArs !== null ? $arsNet + ($usdNet * $usdArs) : null,
                    'usd_bought' => $usdBought,
                    'count' => $items->count(),
                ];
            })
            ->values();

        return view('investments.reports.index', compact(
            'month',
            'year',
            'accountId',
            'accounts',
            'movements',
            'summary',
            'usdBought',
            'usdNetArs',
            'combinedNetArs',
            'byAccount',
            'exchangeRate',
            'usdArs'
        ));
    }
}
