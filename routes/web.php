<?php

use Illuminate\Support\Facades\Route;

// Main landing page
Route::get('/', function () {
    return view('welcome');
});

// SPA catch-all (exclude /api/* to prevent conflicts with API routes)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
?>
