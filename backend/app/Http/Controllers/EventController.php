<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index()
    {
        // Récupérer les événements futurs (ou commencés depuis moins de 2h)
        return Event::where('start_time', '>=', Carbon::now()->subHours(2))
                    ->orderBy('start_time', 'asc')
                    ->get();
    }
}
