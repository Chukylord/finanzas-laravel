<?php

namespace App\Console\Commands;

use App\Models\PriceSnapshot;
use App\Models\WatchlistItem;
use App\Services\IolClient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class IolSyncPrices extends Command
{
    protected $signature = 'iol:sync-prices {--date=}';
    protected $description = 'Sincroniza precios desde IOL para instrumentos de watchlist';

    public function handle(IolClient $iol)
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        // Traemos instrumentos únicos (evita repetir consultas si el mismo instrumento está varias veces)
        $items = WatchlistItem::with('instrument')->get();

        if ($items->count() === 0) {
            $this->info('No hay watchlist_items.');
            return Command::SUCCESS;
        }

        $instruments = $items
            ->pluck('instrument')
            ->filter()                 // por si alguno quedó null
            ->unique('id')
            ->values();

        foreach ($instruments as $inst) {
            $market = $inst->market ?: 'bCBA';   // NO strtolower()
            $symbol = $inst->symbol;

            try {
                $q = $iol->getQuote($market, $symbol);

                // Ajustá según el JSON real que te devuelva IOL
                $close = data_get($q, 'ultimoPrecio');

                if ($close === null) {
                    $this->warn("Sin precio para {$symbol} ({$market})");
                    continue;
                }

                PriceSnapshot::updateOrCreate(
                    ['instrument_id' => $inst->id, 'date' => $date],
                    ['close' => $close]
                );

                $this->info("OK {$symbol} {$date} = {$close}");
            } catch (\Throwable $e) {
                $this->error("ERROR {$symbol}: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
