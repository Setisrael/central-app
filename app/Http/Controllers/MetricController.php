<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Metric;

class MetricController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //

        $validated = $request->validate([
            'instance_id' => 'required|string',
            'prompt_tokens' => 'required|integer',
            'ram_usage' => 'required|integer',
        ]);

        $metric = Metric::create($validated);
        return response()->json(['message' => 'Metrics stored', 'id' => $metric->id], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
