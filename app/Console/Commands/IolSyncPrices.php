<?php

namespace App\Console\Commands;

use App\Models\PriceSnapshot;
use App\Models\WatchlistItem;
use App\Services\IolClient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class IolSyncPrices extends Command
{
    protected $signature = 'iol:sync-prices {--date=} {--debug}';
    protected $description = 'Sincroniza precios desde IOL para instrumentos de la watchlist';

    public function handle(IolClient $iol)
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        $debug = (bool) $this->option('debug');

        // En consola NO usamos Auth
        $items = WatchlistItem::with('instrument')->get();

        if ($items->count() === 0) {
            $this->info('No hay watchlist_items.');
            return Command::SUCCESS;
        }

        foreach ($items as $item) {
            $inst = $item->instrument;

            if (!$inst) {
                $this->warn("Item {$item->id} sin instrument asociado");
                continue;
            }

            // OJO: si tu tabla instruments guarda "bcba" en minúscula, IOL a veces usa "bCBA"
            // Si ya te funciona con "bcba", dejalo. Si falla, probá guardarlo como "bCBA".
            $market = $inst->market ?: 'bCBA';
            $symbol = $inst->symbol;

            try {
                $q = $iol->getQuote($market, $symbol);

                // Campos comunes de IOL
                $close = data_get($q, 'ultimoPrecio',
                         data_get($q, 'ultimo',
                         data_get($q, 'precioUltimo', null)));

                if ($debug) {
                    $this->line("DEBUG {$symbol}: " . json_encode($q, JSON_UNESCAPED_UNICODE));
                }

                if ($close === null) {
                    $this->warn("Sin precio para {$symbol} ({$market})");
                    continue;
                }

                PriceSnapshot::updateOrCreate(
                    ['instrument_id' => $inst->id, 'date' => $date],
                    ['close' => (float) $close]
                );

                $this->info("OK {$symbol} {$date} = {$close}");
            } catch (\Throwable $e) {
                $this->error("ERROR {$symbol}: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
