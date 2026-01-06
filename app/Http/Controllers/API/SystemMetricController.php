<?php

namespace App\Http\Controllers\API;

use App\Models\SystemMetric;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SystemMetricController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->input('data') ?? [$request->all()];
        $saved = [];

        foreach ($data as $metric) {
            $validator = validator($metric, [
                'chatbot_instance_id' => 'required|integer', // |exists:chatbot_instances,id
                'cpu_usage'       => 'required|numeric',
                'ram_usage'       => 'required|numeric',
                'disk_usage'      => 'required|numeric',
                'uptime_seconds'  => 'required|integer',
                'queue_size'      => 'required|integer',
                'timestamp'       => 'required|date',
            ]);

            if ($validator->fails()) {
                Log::warning('SystemMetric validation failed', $validator->errors()->toArray());

                return response()->json([
                    'error' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // ISO 8601 -> Carbon -> MariaDB-kompatibles datetime
            try {
                $validated['timestamp'] = Carbon::parse($validated['timestamp']);
            } catch (\Throwable $e) {
                Log::warning('SystemMetric timestamp parse failed, using now()', [
                    'original' => $validator->validated()['timestamp'] ?? null,
                    'error'    => $e->getMessage(),
                ]);
                $validated['timestamp'] = now();
            }

            $saved[] = SystemMetric::create($validated);
        }

        return response()->json([
            'message' => 'Stored',
            'count'   => count($saved),
            'ids'     => collect($saved)->pluck('id'),
        ], 201);
    }
}
