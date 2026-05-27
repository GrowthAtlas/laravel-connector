<?php

use GrowthAtlas\Connector\Http\Controllers\ConnectorController;
use GrowthAtlas\Connector\Http\Middleware\AuthenticateGrowthAtlas;
use Illuminate\Support\Facades\Route;

$prefix = config('growthatlas-connector.route_prefix', 'api/growthatlas/v1');
$middleware = array_merge(
    config('growthatlas-connector.route_middleware', ['api']),
    [AuthenticateGrowthAtlas::class],
);

Route::prefix($prefix)->middleware($middleware)->group(function () {
    Route::get('/health', [ConnectorController::class, 'health'])->withoutMiddleware([\GrowthAtlas\Connector\Http\Middleware\AuthenticateGrowthAtlas::class]);
    Route::get('/site-profile', [ConnectorController::class, 'siteProfile']);
    Route::get('/pages', [ConnectorController::class, 'pages']);
    Route::get('/entities', [ConnectorController::class, 'entities']);
    Route::post('/content-drafts', [ConnectorController::class, 'createContentDraft']);
});
