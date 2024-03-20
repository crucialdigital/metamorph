<?php

use CrucialDigital\Metamorph\Http\Controllers\MetamorphFormController;
use CrucialDigital\Metamorph\Http\Controllers\MetamorphFormDataController;
use CrucialDigital\Metamorph\Http\Controllers\MetamorphFormInputController;
use CrucialDigital\Metamorph\Http\Controllers\MetamorphFormResourcesController;
use CrucialDigital\Metamorph\Http\Controllers\MasterCrudController;
use CrucialDigital\Metamorph\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/' . config('metamorph.route_prefix', 'metamorph'))
    ->middleware(['api'])->group(function () {
    Route::middleware(config('metamorph.middlewares'))->group(function () {
        Route::post('/search/{entity}', [SearchController::class, 'search']);
        Route::post('/many/search', [SearchController::class, 'findAll']);
        Route::post('/exports/{entity}/{form}', [SearchController::class, 'export']);
        Route::post('/form/{entity}', [MetamorphFormController::class, 'get_form_by_entity']);

        Route::apiResource('/forms', MetamorphFormController::class);
        Route::apiResource('/form-data', MetamorphFormDataController::class);
        Route::apiResource('/form-inputs', MetamorphFormInputController::class)->except(['index']);
        Route::post('/validate/form-data/{id}', [MetamorphFormDataController::class, 'validateFormData']);
        Route::patch('/reject/form-data/{id}', [MetamorphFormDataController::class, 'rejectFormData']);
        Route::apiResource('/master/{entity}', MasterCrudController::class)->except(['index'])->parameters([
            '{entity}' => 'id'
        ]);
    });
    Route::prefix('resources')->group(function () {
        Route::post('/entities', [MetamorphFormResourcesController::class, 'entities']);
        Route::post('/entity/{name}', [MetamorphFormResourcesController::class, 'fetchResources']);
    });
});
