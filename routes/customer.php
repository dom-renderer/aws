<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Frontend\HomeController::class, 'index'])->name('home');

Route::middleware(['auth:customer'])->group(function () {
});

Route::post('state-list', [\App\Helpers\Helper::class, 'getStatesByCountry'])->name('state-list');
Route::post('city-list', [\App\Helpers\Helper::class, 'getCitiesByState'])->name('city-list');