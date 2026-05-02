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

Route::get('/resource', [ResourceController::class, 'index']);
Route::get('/resource/{id}', [ResourceController::class, 'show']);

Route::get('/team/members', [TeamController::class, 'members']);
Route::get('/team/groups', [TeamController::class, 'groups']);

Route::get('/img', [ImageController::class, 'transform']);
Route::get('/img/meta', [ImageController::class, 'meta']);

Route::post('/member-applications', [MemberApplicationController::class, 'store'])
    ->middleware('throttle:10,1');
