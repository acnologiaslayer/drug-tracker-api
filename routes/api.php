<?php<?php



use App\Http\Controllers\Auth\LoginController;use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\Auth\LogoutController;use App\Http\Controllers\Auth\LogoutController;

use App\Http\Controllers\Auth\RegisterController;use App\Http\Controllers\Auth\RegisterController;

use App\Http\Controllers\DrugSearchController;use App\Http\Controllers\DrugSearchController;

use App\Http\Controllers\MedicationController;use App\Http\Controllers\MedicationController;

use Illuminate\Support\Facades\Route;use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {Route::prefix('auth')->group(function () {

    Route::post('register', RegisterController::class);    Route::post('register', RegisterController::class);

    Route::post('login', LoginController::class);    Route::post('login', LoginController::class);

    Route::middleware('auth:sanctum')->post('logout', LogoutController::class);    Route::middleware('auth:sanctum')->post('logout', LogoutController::class);

});});



Route::get('search/drugs', DrugSearchController::class)->middleware('throttle.public-search');Route::get('search/drugs', DrugSearchController::class)->middleware('throttle.public-search');



Route::middleware('auth:sanctum')->group(function () {Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('throttle.authenticated')->group(function () {    Route::get('medications', [MedicationController::class, 'index'])->middleware('throttle.authenticated');

        Route::get('medications', [MedicationController::class, 'index']);    Route::post('medications', [MedicationController::class, 'store'])->middleware('throttle.authenticated');

        Route::post('medications', [MedicationController::class, 'store']);    Route::delete('medications/{rxcui}', [MedicationController::class, 'destroy'])->middleware('throttle.authenticated');

        Route::delete('medications/{rxcui}', [MedicationController::class, 'destroy']);});

    });
});
