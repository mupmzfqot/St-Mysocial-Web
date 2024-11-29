<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index()
    {
        $messages = [];
        return Inertia::render('Messages/Index', compact('messages'));
    }

    public function get($id)
    {
        $messages = [];
        return Inertia::render('Messages/Show', compact('messages'));
    }
}
