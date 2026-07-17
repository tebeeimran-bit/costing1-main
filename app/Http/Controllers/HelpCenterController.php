<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpCenterController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('help.index', ['role' => (string) ($request->user()?->role ?? 'viewer')]);
    }
}
