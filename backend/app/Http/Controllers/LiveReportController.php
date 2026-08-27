<?php

namespace App\Http\Controllers;

use App\Models\LiveReport;
use Illuminate\Http\Request;

class LiveReportController extends Controller
{
    public function index()
    {
        return LiveReport::where('created_at', '>=', now()->subHours(3))
            ->orderByDesc('created_at')
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:power_outage,crowded,event,other',
            'description' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $report = LiveReport::create([
            'type' => $request->type,
            'description' => strip_tags($request->description),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'reporter_name' => auth()->user()->name,
        ]);

        return response()->json($report, 201);
    }
}
