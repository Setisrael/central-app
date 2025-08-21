<?php
namespace App\Http\Controllers\API;

use App\Models\SystemMetric;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SystemMetricController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->input('data') ?? [$request->all()];
        $saved = [];

        foreach ($data as $metric) {
            $validator = validator($metric, [
                'chatbot_instance_id' => 'required|integer', //|exists:chatbot_instances,id'
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
                    'error' => $validator->errors(),], 422);
               // continue;
            }

            $saved[] = SystemMetric::create($validator->validated());
        }

        return response()->json([
            'message' => ' Stored',
            'count' => count($saved),
            'ids' => collect($saved)->pluck('id'),
        ], 201);
    }

}

