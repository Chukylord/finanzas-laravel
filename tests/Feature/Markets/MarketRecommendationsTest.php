<?php

use App\Models\Instrument;
use App\Models\PriceSnapshot;
use App\Models\User;
use App\Models\WatchlistItem;
use App\Services\IolClient;
use Illuminate\Support\Facades\Auth;

it('muestra recomendaciones de compra/venta usando cotizaciones de IOL', function () {
    $user = User::factory()->create();

    $instrument = Instrument::create([
        'symbol' => 'GGAL',
        'name' => 'Grupo Galicia',
        'type' => 'accion',
        'market' => 'bCBA',
        'currency' => 'ARS',
        'active' => true,
    ]);

    WatchlistItem::create([
        'user_id' => $user->id,
        'instrument_id' => $instrument->id,
        'horizon' => 'short',
    ]);

    PriceSnapshot::create([
        'instrument_id' => $instrument->id,
        'date' => now()->subDay()->toDateString(),
        'close' => 1000,
    ]);

    $mock = Mockery::mock(IolClient::class);
    $mock->shouldReceive('getQuote')
        ->once()
        ->with('bCBA', 'GGAL')
        ->andReturn([
            'ultimoPrecio' => 1035,
            'variacionPorcentual' => 2.4,
        ]);

    app()->instance(IolClient::class, $mock);

    $this->withSession([]);
    Auth::login($user);

    $response = $this->get('/markets');

    $response->assertOk();
    $response->assertSee('Recomendaciones IOL', false);
    $response->assertSee('COMPRAR', false);
    $response->assertSee('GGAL', false);
});
