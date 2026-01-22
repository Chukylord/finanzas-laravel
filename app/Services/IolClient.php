<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class IolClient
{
    private string $baseUrl;
    private string $apiToken;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.iol.base_url'), '/');
        $this->apiToken = (string) config('services.iol.api_token');
    }

    private function authHeaders(): array
    {
        if (!$this->apiToken) {
            throw new \RuntimeException('Falta IOL_API_TOKEN en el .env');
        }

        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Accept' => 'application/json',
        ];
    }

    public function getQuote(string $market, string $symbol): array
    {
        // Endpoint según tu doc:
        // GET /api/v2/{Mercado}/Titulos/{Simbolo}/Cotizacion
        $market = trim($market);
        $symbol = trim($symbol);

        $url = $this->baseUrl . "/api/v2/{$market}/Titulos/{$symbol}/Cotizacion";

        /** @var Response $resp */
        $resp = Http::withHeaders($this->authHeaders())
            ->timeout(20)
            ->get($url);

        if (!$resp->successful()) {
            throw new \RuntimeException("IOL quote error ({$market} {$symbol}): " . $resp->body());
        }

        return $resp->json();
    }
}
