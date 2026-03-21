<?php

namespace App\Console\Commands;

use App\Models\Instrument;
use App\Models\PriceSnapshot;
use App\Services\IolClient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class IolBackfillCedears extends Command
{
    protected $signature = 'iol:backfill-cedears
        {--days=120 : Cantidad de días hacia atrás}
        {--adjusted=0 : 1=ajustada, 0=no ajustada}
        {--sleep=120 : Milisegundos de pausa entre requests}
        {--chunk=50 : Tamaño de lote por chunk}
        {--limit=0 : Limitar cantidad de instrumentos (debug). 0 = sin límite}
        {--symbol= : Un solo símbolo (debug)}
        {--fail-fast : Corta al primer error}
        {--debug : Muestra debug}
    ';

    protected $description = 'Backfill de serie histórica (últimos N días) para CEDEAR y guarda en price_snapshots (OHLCV)';

    public function handle(IolClient $iol)
    {
        $days     = (int) $this->option('days');
        $adjusted = ((int) $this->option('adjusted')) === 1;
        $sleepMs  = (int) $this->option('sleep');
        $chunk    = max(10, (int) $this->option('chunk'));
        $limit    = (int) $this->option('limit');
        $debug    = (bool) $this->option('debug');
        $symbol   = $this->option('symbol') ? strtoupper(trim((string) $this->option('symbol'))) : null;
        $failFast = (bool) $this->option('fail-fast');

        $to   = Carbon::today();
        $from = Carbon::today()->subDays(max(1, $days));

        $q = Instrument::query()
            ->where('type', 'cedear')
            ->where('active', true)
            ->orderBy('id');

        if ($symbol) {
            $q->where('symbol', $symbol);
        } elseif ($limit > 0) {
            $q->limit($limit);
        }

        $total = $q->count();
        if ($total === 0) {
            $this->warn('No hay instrumentos CEDEAR para backfill.');
            return Command::SUCCESS;
        }

        $this->info(
            "Backfill CEDEAR: {$total} instrumentos | {$from->toDateString()} → {$to->toDateString()} | adjusted=" .
            ($adjusted ? '1' : '0') .
            ($symbol ? " | symbol={$symbol}" : '')
        );

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $snapUpserts = 0;
        $instOk = 0;
        $instFail = 0;

        $q->chunkById($chunk, function ($instruments) use (
            $iol, $from, $to, $adjusted, $sleepMs, $debug, $failFast,
            &$snapUpserts, &$instOk, &$instFail, $bar
        ) {
            foreach ($instruments as $inst) {
                try {
                    // IMPORTANTE: en tu caso market puede venir "bcba" o "1" según lo que guardaste.
                    // Si te quedó "1" y te anda igual, lo dejamos tal cual.
                    $market = trim((string) $inst->market);
                    if ($market === '') $market = 'bcba';

                    $rows = $iol->getSerieHistorica(
                        $market,
                        $inst->symbol,
                        $from->toDateString(),
                        $to->toDateString(),
                        $adjusted
                    );

                    // Detectar serie en distintas keys, o lista directa
                    $series =
                        data_get($rows, 'serieHistorica')
                        ?? data_get($rows, 'SerieHistorica')
                        ?? data_get($rows, 'serie')
                        ?? data_get($rows, 'Series')
                        ?? data_get($rows, 'data')
                        ?? data_get($rows, 'datos')
                        ?? data_get($rows, 'cotizaciones')
                        ?? data_get($rows, 'items')
                        ?? $rows;

                    if (!is_array($series)) $series = [];

                    // Si es array asociativo (no lista), lo descartamos
                    if (is_array($series) && array_keys($series) !== range(0, count($series) - 1)) {
                        $series = [];
                    }

                    if ($debug) {
                        $this->newLine();
                        $this->info("DEBUG {$inst->symbol} market={$market}");
                        $this->info("DEBUG items=" . count($series));
                        if (count($series) > 0 && is_array($series[0])) {
                            $this->info("DEBUG keys(first): " . implode(',', array_slice(array_keys($series[0]), 0, 30)));
                        }
                    }

                    foreach ($series as $r) {
                        $dateStr =
                            data_get($r, 'fechaHora')
                            ?? data_get($r, 'fecha')
                            ?? data_get($r, 'FechaHora')
                            ?? data_get($r, 'Fecha')
                            ?? null;

                        if (!$dateStr) continue;

                        $d = Carbon::parse($dateStr)->toDateString();

                        // Campos típicos que vimos en tu debug:
                        // ultimoPrecio, variacion, apertura, maximo, minimo, volumenNominal, moneda
                        $close = data_get($r, 'ultimoPrecio') ?? data_get($r, 'cierre') ?? data_get($r, 'precio');
                        if ($close === null) continue;

                        $open   = data_get($r, 'apertura');
                        $high   = data_get($r, 'maximo');
                        $low    = data_get($r, 'minimo');

                        // en CEDEAR “Todos” venía volumenNominal, en histórico puede variar
                        $volume = data_get($r, 'volumenNominal')
                            ?? data_get($r, 'volumen')
                            ?? data_get($r, 'volume');

                        $changePct = data_get($r, 'variacionPorcentual')
                            ?? data_get($r, 'variacion');

                        $currencyRaw = (string) (data_get($r, 'moneda') ?? '');
                        $currency = $currencyRaw ? (str_contains(strtolower($currencyRaw), 'peso') ? 'ARS' : strtoupper(trim($currencyRaw))) : null;

                        PriceSnapshot::updateOrCreate(
                            ['instrument_id' => $inst->id, 'date' => $d],
                            [
                                'open'       => $open !== null ? (float)$open : null,
                                'high'       => $high !== null ? (float)$high : null,
                                'low'        => $low !== null ? (float)$low : null,
                                'close'      => (float)$close,
                                'volume'     => $volume !== null ? (int)$volume : null,
                                'currency'   => $currency,
                                'change_pct' => $changePct !== null ? (float)$changePct : null,
                                'fetched_at' => now(),
                            ]
                        );

                        $snapUpserts++;
                    }

                    $instOk++;
                } catch (\Throwable $e) {
                    $instFail++;
                    $this->newLine();
                    $this->error("ERROR {$inst->symbol}: " . $e->getMessage());

                    if ($failFast) {
                        throw $e;
                    }
                }

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("OK Backfill terminado ✅");
        $this->info("Instrumentos OK: {$instOk} | FAIL: {$instFail}");
        $this->info("Snapshots upsert total (aprox): {$snapUpserts}");

        return Command::SUCCESS;
    }
}
