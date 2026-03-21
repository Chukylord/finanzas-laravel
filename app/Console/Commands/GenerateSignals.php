<?php

namespace App\Console\Commands;

use App\Models\Instrument;
use App\Models\PriceSnapshot;
use App\Models\Signal;
use App\Models\SignalRule;
use App\Models\User;
use App\Services\IndicatorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSignals extends Command
{
    protected $signature = 'signals:generate
        {--date= : Fecha (YYYY-MM-DD). Default hoy}
        {--user=0 : User ID específico (0 = todos)}
        {--limit=0 : Limitar instrumentos (debug)}
        {--debug : Muestra debug}
    ';

    protected $description = 'Genera señales SMA/EMA/RSI por horizonte y las guarda en signals';

    public function handle(IndicatorService $ind)
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        $userId = (int) $this->option('user');
        $limit  = (int) $this->option('limit');
        $debug  = (bool) $this->option('debug');

        $usersQ = User::query();
        if ($userId > 0) $usersQ->where('id', $userId);
        $users = $usersQ->get();

        if ($users->count() === 0) {
            $this->warn('No hay usuarios para generar señales.');
            return Command::SUCCESS;
        }

        // Universo: todos los CEDEAR activos (podés ampliar a accion/fci/crypto después)
        $instQ = Instrument::query()
            ->where('active', true)
            ->whereIn('type', ['cedear']);

        if ($limit > 0) $instQ->limit($limit);

        $instruments = $instQ->get();
        if ($instruments->count() === 0) {
            $this->warn('No hay instrumentos activos.');
            return Command::SUCCESS;
        }

        $this->info("Generando señales para {$users->count()} usuario(s) | instrumentos={$instruments->count()} | date={$date}");

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($users as $u) {

            // Reglas del usuario (si no hay, usamos defaults)
            $rules = SignalRule::where('user_id', $u->id)->where('active', true)->get()->keyBy('horizon');

            $defaults = [
                'short'  => ['fast' => 10, 'slow' => 20, 'rsi' => 14],
                'medium' => ['fast' => 20, 'slow' => 50, 'rsi' => 14],
                'long'   => ['fast' => 50, 'slow' => 200, 'rsi' => 14],
            ];

            foreach ($instruments as $inst) {

                // necesitamos suficientes días para long (200) => pedimos 260 para estar seguros
                $snaps = PriceSnapshot::where('instrument_id', $inst->id)
                    ->where('date', '<=', $date)
                    ->orderBy('date', 'desc')
                    ->limit(260)
                    ->get()
                    ->reverse()
                    ->values();

                if ($snaps->count() < 60) { // mínimo razonable para short/medium
                    $skipped += 3;
                    continue;
                }

                $closes = $snaps->pluck('close')->map(fn($v) => (float)$v)->toArray();
                $lastPrice = (float) end($closes);

                foreach (['short','medium','long'] as $horizon) {

                    $fast = $defaults[$horizon]['fast'];
                    $slow = $defaults[$horizon]['slow'];
                    $rsiP = $defaults[$horizon]['rsi'];

                    if (isset($rules[$horizon])) {
                        $fast = (int) $rules[$horizon]->fast_ma;
                        $slow = (int) $rules[$horizon]->slow_ma;
                    }

                    // Validaciones mínimas
                    if ($fast <= 0 || $slow <= 0 || $fast >= $slow) {
                        $fast = $defaults[$horizon]['fast'];
                        $slow = $defaults[$horizon]['slow'];
                    }

                    // Indicadores
                    $smaFast = $ind->sma($closes, $fast);
                    $smaSlow = $ind->sma($closes, $slow);
                    $emaFast = $ind->ema($closes, $fast);
                    $emaSlow = $ind->ema($closes, $slow);
                    $rsi = $ind->rsi($closes, $rsiP);

                    if ($smaFast === null || $smaSlow === null || $emaFast === null || $emaSlow === null || $rsi === null) {
                        $skipped++;
                        continue;
                    }

                    // Lógica simple (mix):
                    // - BUY: SMA fast > SMA slow y RSI < 60 (no sobrecomprado)
                    // - SELL: SMA fast < SMA slow o RSI > 70
                    // - HOLD: resto
                    $action = 'hold';
                    $reasonParts = [];

                    if ($smaFast > $smaSlow) $reasonParts[] = "SMA{$fast} > SMA{$slow}";
                    else $reasonParts[] = "SMA{$fast} < SMA{$slow}";

                    $reasonParts[] = "RSI=" . number_format($rsi, 2, '.', '');

                    if ($smaFast > $smaSlow && $rsi < 60) {
                        $action = 'buy';
                    } elseif ($smaFast < $smaSlow || $rsi > 70) {
                        $action = 'sell';
                    }

                    // Score simple 0..100
                    $score = 50;
                    $trendBonus = ($smaFast > $smaSlow) ? 20 : -20;
                    $rsiBonus = 0;

                    if ($rsi < 30) $rsiBonus = 25;
                    elseif ($rsi < 60) $rsiBonus = 10;
                    elseif ($rsi > 70) $rsiBonus = -25;
                    elseif ($rsi > 60) $rsiBonus = -10;

                    $score = max(0, min(100, $score + $trendBonus + $rsiBonus));

                    $reason = implode(' · ', $reasonParts);

                    // Guardar (upsert por día/horizonte)
                    $signal = Signal::where('user_id', $u->id)
                        ->where('instrument_id', $inst->id)
                        ->where('horizon', $horizon)
                        ->where('date', $date)
                        ->first();

                    $payload = [
                        'action'      => $action,
                        'score'       => $score,
                        'reason'      => $reason,
                        'signal_type' => 'mix',
                        'price'       => $lastPrice,
                        'rsi'         => $rsi,
                        'fast_ma'     => $fast,
                        'slow_ma'     => $slow,
                    ];

                    if (!$signal) {
                        Signal::create(array_merge($payload, [
                            'user_id' => $u->id,
                            'instrument_id' => $inst->id,
                            'horizon' => $horizon,
                            'date' => $date,
                        ]));
                        $created++;
                    } else {
                        $signal->update($payload);
                        $updated++;
                    }

                    if ($debug && $inst->symbol === 'KO') {
                        $this->info("DEBUG KO {$horizon}: {$action} score={$score} price={$lastPrice} {$reason}");
                    }
                }
            }
        }

        $this->info("OK ✅ signals creadas={$created} | actualizadas={$updated} | omitidas={$skipped}");
        return Command::SUCCESS;
    }
}
