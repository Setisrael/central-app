<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MetricController;

use App\Http\Controllers\API\MetricUsageController;
use App\Http\Controllers\API\SystemMetricController;
use App\Http\Controllers\API\ChatbotRegistrationController;

Request::macro('expectsJson', function () {
    return true;
});


Route::middleware('auth:sanctum')->post('/metrics', [MetricController::class, 'store']);
//Route::apiResource("metrics", MetricController::class);

/*Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');*/

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/metric-usage', [MetricUsageController::class, 'store']);
    Route::post('/system-metrics', [SystemMetricController::class, 'store']);
});
Route::post('/register-chatbot', [ChatbotRegistrationController::class, 'register']);

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});
