<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Persist a UI locale choice. Stored on the user (when authenticated) and
 * mirrored into the session so the login form remembers the preference.
 * Always redirects back to the referrer to keep the flow seamless.
 */
Route::post('/admin/locale', function (Request $request) {
    $locale = strtolower((string) $request->input('locale', ''));
    if (! in_array($locale, SetLocale::SUPPORTED, true)) {
        return back();
    }

    $request->session()->put('locale', $locale);

    if ($user = $request->user()) {
        $user->forceFill(['locale' => $locale])->save();
    }

    return redirect($request->input('redirect', $request->headers->get('referer') ?: '/admin'));
})->middleware(['web'])->name('admin.locale.set');

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

if (app()->environment('local')) {
    Route::get('/__dev/auto-login/{id}', function (int $id) {
        \Illuminate\Support\Facades\Auth::loginUsingId($id);
        return redirect('/admin');
    });
}
