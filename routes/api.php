<?php

use App\Http\Controllers\Api\V1\PingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 routes
|--------------------------------------------------------------------------
|
| Every route in this file is served under the "/api/v1" prefix (see
| "apiPrefix" in bootstrap/app.php) and uses the stateless "api" middleware
| group. Route names are prefixed with "api.v1.".
|
*/

Route::name('api.v1.')->group(function (): void {
    Route::get('ping', PingController::class)->name('ping');
});
