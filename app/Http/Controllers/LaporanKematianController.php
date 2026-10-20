<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LaporanKematianController extends Controller
{
    public function create(): View
    {
        return view('laporan.create');
    }
}