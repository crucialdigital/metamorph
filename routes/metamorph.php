<?php

use CrucialDigital\Metamorph\Http\Controllers\CoreFormController;
use CrucialDigital\Metamorph\Http\Controllers\CoreFormDataController;
use CrucialDigital\Metamorph\Http\Controllers\CoreFormInputController;
use CrucialDigital\Metamorph\Http\Controllers\CoreFormResourcesController;
use CrucialDigital\Metamorph\Http\Controllers\MasterCrudController;
use CrucialDigital\Metamorph\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/metamorph')->middleware(['api'])->group(function () {
    Route::middleware(config('metamorph.middleware'))->group(function (){
        Route::post('/search/{entity}', [SearchController::class, 'search']);
        Route::post('/many/search', [SearchController::class, 'findAll']);
        Route::apiResource('/forms', CoreFormController::class);
        Route::apiResource('/form-data', CoreFormDataController::class);
        Route::apiResource('/form-inputs', CoreFormInputController::class)->except(['index']);
        Route::prefix('resources')->group(function () {
            Route::post('/entities', [CoreFormResourcesController::class, 'entities']);
            Route::post('/entity/{name}', [CoreFormResourcesController::class, 'fetchResources']);
        });
        Route::post('/exports/{entity}/{form}', [SearchController::class, 'export']);
        Route::post('/validate/form-data/{id}', [CoreFormDataController::class, 'validateFormData']);
        Route::patch('/reject/form-data/{id}', [CoreFormDataController::class, 'rejectFormData']);

        Route::delete('/master/{entity}', [MasterCrudController::class, 'erase']);
        Route::apiResource('/master/{entity}', MasterCrudController::class)->except(['index'])->parameters([
            '{entity}' => 'id'
        ]);
    });
});
