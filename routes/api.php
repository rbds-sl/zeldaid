<?php

declare(strict_types=1);

use Apps\Api\Internal\InternalController;
use Apps\Api\RestaurantClient\RestaurantClientController;
use App\Http\Controllers\WalletPassController;
use Illuminate\Support\Facades\Route;


Route::get('/internal/queue/info', [InternalController::class, 'queueInfo']);

// Wallet Pass Web Service endpoints (según Apple documentation)
Route::prefix('v1')->group(function () {
    // Registrar dispositivo para notificaciones
    Route::post('/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}', 
        [WalletPassController::class, 'registerDevice']);
    
    // Desregistrar dispositivo
    Route::delete('/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}', 
        [WalletPassController::class, 'unregisterDevice']);
    
    // Obtener passes actualizados para dispositivo
    Route::get('/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}', 
        [WalletPassController::class, 'getUpdatedPasses']);
    
    // Obtener pass individual
    Route::get('/passes/{passTypeIdentifier}/{serialNumber}', 
        [WalletPassController::class, 'getPass']);
    
    // Log de errores
    Route::post('/log', [WalletPassController::class, 'logError']);
    
    // Gestión de passes (endpoints adicionales)
    Route::post('/passes', [WalletPassController::class, 'createPass']);
    Route::put('/passes/{passTypeIdentifier}/{serialNumber}', [WalletPassController::class, 'updatePass']);
});

// Apply bearer token authentication to all API routes
Route::middleware('machine.token')->group(function () {
    // Group endpoints

    // RestaurantClient endpoints
    Route::post('/restaurant-client/insertBulk', [RestaurantClientController::class, 'insertBulk']);
    Route::post('/restaurant-client/create', [RestaurantClientController::class, 'create']);

});
