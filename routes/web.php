<?php

use App\Http\Controllers\CharacterController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CharacterController::class, 'index'])
    ->name('characters.index');

Route::get('/characters/{id}', [CharacterController::class, 'show'])
    ->whereNumber('id')
    ->name('characters.show');