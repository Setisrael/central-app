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
      /*
        try {
            $validated = $request->validate([
                'instance_id' => 'required|string|max:255',
                'prompt_tokens' => 'required|integer|min:0',
                'ram_usage' => 'required|integer|min:0',
            ]);

            $metric = Metric::create($validated);

            // Minimal, safe logging (optional!)
            \Log::info('Metric stored', [
                'instance_id' => $validated['instance_id'],
                'tokens' => $validated['prompt_tokens'],
                'ram' => $validated['ram_usage'],
            ]);

            return response()->json([
                'message' => 'Metrics stored',
                'id' => $metric->id
            ], 201);

        } catch (\Throwable $e) {
            // 🧠 Catch any weird errors and avoid Monolog recursion
            \Log::error('Metric storage failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to store metrics',
                'details' => $e->getMessage(),
            ], 500);
        }*/
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
