<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class IolClient
{
    private string $baseUrl;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->baseUrl  = rtrim((string) config('services.iol.base_url', 'https://api.invertironline.com'), '/');
        $this->username = (string) config('services.iol.username');
        $this->password = (string) config('services.iol.password');
    }

    public function bearerToken(): string
    {
        return Cache::remember('iol_bearer_token', now()->addMinutes(10), function () {
            if (!$this->username || !$this->password) {
                throw new \RuntimeException('Faltan IOL_USERNAME o IOL_PASSWORD en el .env');
            }

            /** @var Response $resp */
            $resp = Http::asForm()
                ->timeout(20)
                ->post($this->baseUrl . '/token', [
                    'username'   => $this->username,
                    'password'   => $this->password,
                    'grant_type' => 'password',
                ]);

            if (!$resp->successful()) {
                throw new \RuntimeException('IOL token error HTTP ' . $resp->status() . ': ' . $resp->body());
            }

            $token = $resp->json('access_token');
            if (!$token) {
                throw new \RuntimeException('IOL token error: no vino access_token');
            }

            return (string) $token;
        });
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->bearerToken(),
            'Accept'        => 'application/json',
        ];
    }

    public function getQuote(string $market, string $symbol): array
    {
        $market = trim($market);
        $symbol = trim($symbol);

        $url = $this->baseUrl . "/api/v2/{$market}/Titulos/{$symbol}/Cotizacion";

        /** @var Response $resp */
        $resp = Http::withHeaders($this->authHeaders())
            ->timeout(20)
            ->get($url);

        if (!$resp->successful()) {
            throw new \RuntimeException("IOL quote error ({$market} {$symbol}) HTTP {$resp->status()}: " . $resp->body());
        }

        return (array) $resp->json();
    }

    public function getQuotesAll(string $instrumento, string $pais): array
    {
        $instrumento = trim($instrumento); // ej: "cedears"
        $pais        = trim($pais);        // ej: "argentina"

        $url = $this->baseUrl . "/api/v2/Cotizaciones/{$instrumento}/{$pais}/Todos";

        /** @var Response $resp */
        $resp = Http::withHeaders($this->authHeaders())
            ->timeout(30)
            ->get($url);

        if (!$resp->successful()) {
            throw new \RuntimeException("IOL quotesAll error ({$instrumento} {$pais}) HTTP {$resp->status()}: " . $resp->body());
        }

        return (array) $resp->json();
    }

    /**
     * Serie histórica: IOL es quisquilloso.
     * Probamos variantes de:
     * - market (bcba/bCBA/BCBA)
     * - fechas (Y-m-d y d-m-Y)
     * - ajustada (ajustada/noajustada/true/false/0/1)
     *
     * Devuelve el JSON (array) de IOL. Si ninguna variante trae data, devuelve [] (y no rompe).
     * Si IOL responde 401/403, ahí sí lanzamos error (porque es auth).
     */
    public function getSerieHistorica(string $market, string $symbol, string $from, string $to, bool $adjusted): array
    {
        $market = trim($market);
        $symbol = trim($symbol);

        // candidatos de market: el que viene + variantes típicas
        $marketCandidates = array_values(array_unique(array_filter([
            $market,
            strtolower($market),
            strtoupper($market),
            'bCBA',
            'BCBA',
            'bcba',
        ])));

        // candidatos de fechas
        $fromYmd = $from;
        $toYmd   = $to;

        // si por las dudas te pasan fechas con otro formato, las normalizamos rápido
        try { $fromYmd = \Carbon\Carbon::parse($from)->format('Y-m-d'); } catch (\Throwable $e) {}
        try { $toYmd   = \Carbon\Carbon::parse($to)->format('Y-m-d'); } catch (\Throwable $e) {}

        $fromDmy = \Carbon\Carbon::parse($fromYmd)->format('d-m-Y');
        $toDmy   = \Carbon\Carbon::parse($toYmd)->format('d-m-Y');

        $datePairs = [
            [$fromYmd, $toYmd],
            [$fromDmy, $toDmy],
        ];

        // candidatos ajustada
        $adjCandidates = $adjusted
            ? ['ajustada', 'true', '1']
            : ['noajustada', 'false', '0', 'no-ajustada', 'noAjustada'];

        // también probamos la otra por si IOL está “invertido”
        $adjCandidates = array_values(array_unique(array_merge(
            $adjCandidates,
            ['ajustada','noajustada','true','false','1','0']
        )));

        $lastErr = null;

        foreach ($marketCandidates as $m) {
            foreach ($datePairs as [$fd, $td]) {
                foreach ($adjCandidates as $adj) {

                    $url = $this->baseUrl . "/api/v2/{$m}/Titulos/{$symbol}/Cotizacion/seriehistorica/{$fd}/{$td}/{$adj}";

                    /** @var Response $resp */
                    $resp = Http::withHeaders($this->authHeaders())
                        ->timeout(40)
                        ->get($url);

                    // Auth mala => cortamos (no tiene sentido seguir)
                    if (in_array($resp->status(), [401, 403], true)) {
                        throw new \RuntimeException("IOL seriehistorica AUTH error ({$m} {$symbol}) HTTP {$resp->status()} | URL={$url} | BODY=" . $resp->body());
                    }

                    if (!$resp->successful()) {
                        // 400/404/500: guardamos para debug y seguimos probando variantes
                        $lastErr = "HTTP {$resp->status()} | URL={$url} | BODY=" . $resp->body();
                        continue;
                    }

                    $json = $resp->json();

                    // A veces viene como [] directo, a veces viene envuelto.
                    $series =
                        data_get($json, 'serieHistorica')
                        ?? data_get($json, 'SerieHistorica')
                        ?? data_get($json, 'serie')
                        ?? data_get($json, 'Series')
                        ?? $json;

                    if (is_array($series) && count($series) > 0) {
                        // devolvemos el json original para que tu command pueda extraer bien
                        return (array) $json;
                    }
                }
            }
        }

        // Si ninguna variante trajo data, devolvemos vacío (y no rompemos el comando).
        // Si querés que “rompa”, cambiá esto por throw con $lastErr.
        return [];
    }
}
