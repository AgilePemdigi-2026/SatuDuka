<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaporanKematianController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pengajuan-laporan', [
    LaporanKematianController::class,
    'create'
])->name('laporan.create');