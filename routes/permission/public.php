<?php

use Illuminate\Support\Facades\Route;

// B9 shortcut: situs publik belum ada; diganti beranda publik di M2
Route::redirect('/', '/login')->name('site.home');
