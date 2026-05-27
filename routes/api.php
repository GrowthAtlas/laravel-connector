<?php

use GrowthAtlas\Connector\Http\Controllers\ConnectorController;
use GrowthAtlas\Connector\Http\Middleware\AuthenticateGrowthAtlas;
use Illuminate\Support\Facades\Route;

Route::middleware([AuthenticateGrowthAtlas::class])->group(function () {
    Route::get('/health', [ConnectorController::class, 'health'])->withoutMiddleware([AuthenticateGrowthAtlas::class]);
    Route::get('/site-profile', [ConnectorController::class, 'siteProfile']);
    Route::get('/pages', [ConnectorController::class, 'pages']);
    Route::get('/entities', [ConnectorController::class, 'entities']);
    Route::post('/content-drafts', [ConnectorController::class, 'createContentDraft']);
});
