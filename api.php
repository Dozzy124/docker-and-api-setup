<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| API-only Laravel app — every route here returns JSON, no Blade views.
| Consumed by two clients: Next.js (SPA/cookie via Sanctum) and Expo
| (Bearer token via Sanctum). See ТЗ §3 for the auth split.
*/

// No auth required — used by both frontend teams and infra monitoring
// to verify the API, DB and Redis are all reachable.
Route::get('/health', HealthController::class)->name('api.health');

// --- Auth routes (§3.4), documents / indicators / chat (§4) get added
//     here by the corresponding feature branches. Left out of this
//     branch (docker-and-api-setup) on purpose — infra/bootstrap only.
