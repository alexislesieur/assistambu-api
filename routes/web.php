<?php

use Illuminate\Support\Facades\Route;

// Route nommée 'login' requise par Laravel pour les redirections internes
// L'utilisateur n'accède jamais directement à cette route
Route::get('/login', function () {
    return response()->json(['message' => 'Non authentifié.'], 401);
})->name('login');