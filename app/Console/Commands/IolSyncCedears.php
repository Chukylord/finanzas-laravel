<?php

namespace App\Console\Commands;

use App\Models\Instrument;
use App\Models\PriceSnapshot;
use App\Services\IolClient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class IolSyncCedears extends Command
{
    protected $signature = 'iol:sync-cedears {--date=} {--country=argentina} {--debug}';
    protected $description = 'Sincroniza universo de CEDEAR desde IOL y guarda precio del día (OHLCV) en price_snapshots';

    public function handle(IolClient $iol)
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        $country = $this->option('country') ?: 'argentina';

        $raw  = $iol->getQuotesAll('cedears', $country);
        $rows = is_array($raw) ? ($raw['titulos'] ?? []) : [];

        if ($this->option('debug')) {
            $this->info('DEBUG count(rows): ' . (is_array($rows) ? count($rows) : 0));
            if (is_array($rows) && count($rows) > 0) {
                $this->info('DEBUG keys(first row): ' . implode(',', array_slice(array_keys($rows[0]), 0, 30)));
            }
        }

        if (!is_array($rows) || count($rows) === 0) {
            $this->warn('IOL devolvió vacío (titulos).');
            return Command::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $snapOk  = 0;

        foreach ($rows as $r) {
            $symbol = data_get($r, 'simbolo');
            if (!$symbol) continue;

            $name = data_get($r, 'descripcion') ?? data_get($r, 'descripcionTitulo') ?? $symbol;

            $marketRaw = (string) (data_get($r, 'mercado') ?? 'bcba');
            $market    = strtolower(trim($marketRaw));
            if ($market === '' || $market === '1') $market = 'bcba';

            $currencyRaw = (string) (data_get($r, 'moneda') ?? 'ARS');
            $currency = str_contains(strtolower($currencyRaw), 'peso') ? 'ARS' : strtoupper(trim($currencyRaw));

            // OHLCV del “Todos”
            $close = data_get($r, 'ultimoPrecio');
            if ($close === null) continue;

            $open   = data_get($r, 'apertura');
            $high   = data_get($r, 'maximo');
            $low    = data_get($r, 'minimo');
            $volume = data_get($r, 'volumenNominal') ?? data_get($r, 'volumen');
            $changePct = data_get($r, 'variacionPorcentual') ?? data_get($r, 'variacion');

            $instrument = Instrument::where('symbol', $symbol)->first();

            if (!$instrument) {
                $instrument = Instrument::create([
                    'symbol'   => $symbol,
                    'name'     => $name,
                    'type'     => 'cedear',
                    'market'   => $market,
                    'currency' => $currency,
                    'active'   => true,
                ]);
                $created++;
            } else {
                $instrument->update([
                    'name'     => $name,
                    'type'     => 'cedear',
                    'market'   => $market ?: ($instrument->market ?: 'bcba'),
                    'currency' => $currency ?: ($instrument->currency ?: 'ARS'),
                    'active'   => true,
                ]);
                $updated++;
            }

            PriceSnapshot::updateOrCreate(
                ['instrument_id' => $instrument->id, 'date' => $date],
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

            $snapOk++;
        }

        $this->info("OK. Instruments creados: {$created} | actualizados: {$updated} | snapshots: {$snapOk} | fecha={$date}");
        return Command::SUCCESS;
    }
}
