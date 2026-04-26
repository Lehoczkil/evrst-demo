<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Serve files from the public storage disk through Laravel so the
 * HandleCors middleware can attach Access-Control-Allow-Origin headers
 * during dev (the SPA on :5173 fetches /storage/<path> cross-origin).
 * In production this never matches because nginx serves /storage/*
 * directly via the public/storage symlink.
 */
Route::get('/storage/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return response()->file(Storage::disk('public')->path($path), [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.+');
