<?php

use App\Http\Controllers\Api\MemberApplicationController;
use App\Http\Controllers\Api\ResourceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/resource', [ResourceController::class, 'index']);
Route::get('/resource/{id}', [ResourceController::class, 'show']);

Route::post('/member-applications', [MemberApplicationController::class, 'store'])
    ->middleware('throttle:10,1');
