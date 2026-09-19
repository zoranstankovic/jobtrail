<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

abstract class Controller
{
    /**
     * Show a toast on the next page (docs/design.md §6.6). Inertia flash data
     * reaches the browser once and is not kept in its history.
     */
    protected function toast(string $message, string $type = 'success'): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
