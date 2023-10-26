<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::view('counter', 'counter');
Route::view('injection', 'injection');
Route::view('conditionals', 'conditionals');
Route::view('s3i', 'server-side-script-injection');
