<?php

use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\MemberApplicationController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Everything below is public and unauthenticated, so it is all throttled.
// The two limiters are defined in AppServiceProvider::configureRateLimiters().
Route::middleware('throttle:api')->group(function () {
    Route::get('/resource', [ResourceController::class, 'index']);
    Route::get('/resource/{id}', [ResourceController::class, 'show']);

    Route::get('/team/members', [TeamController::class, 'members']);
    Route::get('/team/groups', [TeamController::class, 'groups']);

    // The join-us form definition — sections, questions and choices,
    // editable under Membership → Application form.
    Route::get('/application-form', [MemberApplicationController::class, 'form']);
});

// A page load asks for many of these at once, and a miss writes a file to
// the app-data volume — hence its own, higher, limit.
Route::middleware('throttle:images')->group(function () {
    Route::get('/img', [ImageController::class, 'transform']);
    Route::get('/img/meta', [ImageController::class, 'meta']);
});

Route::post('/member-applications', [MemberApplicationController::class, 'store'])
    ->middleware('throttle:10,1');
