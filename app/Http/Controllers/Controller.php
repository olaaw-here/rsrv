<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Ambil per_page dari request dengan batas aman (1..$max) supaya klien
     * tidak bisa meminta ribuan baris sekaligus.
     */
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min(max($request->integer('per_page', $default), 1), $max);
    }
}
