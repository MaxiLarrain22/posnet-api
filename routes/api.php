<?php

use App\Http\Controllers\Posnet\CreateCardController;
use App\Http\Controllers\Posnet\GetFeesController;
use App\Http\Controllers\Posnet\ListCardsController;
use App\Http\Controllers\Posnet\DoPaymentController;
use App\Http\Controllers\Posnet\TransactionHistoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('cards', CreateCardController::class);          // Registrar tarjeta
Route::get('cards', ListCardsController::class);            // Listar tarjetas
Route::post('payments', DoPaymentController::class);   // Procesar pago
Route::get('fees', GetFeesController::class); // Obtener cuotas disponibles
Route::get('transactions', TransactionHistoryController::class); // Historial de transacciones