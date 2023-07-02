<?php

use Illuminate\Support\Facades\Route;

Route::view('counter', 'tests::counter');
Route::view('injection', 'tests::injection');
Route::view('conditionals', 'tests::conditionals');
Route::view('s3i', 'tests::server-side-script-injection');
