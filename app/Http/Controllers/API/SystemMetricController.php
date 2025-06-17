<?php
namespace App\Http\Controllers\API;

use App\Models\SystemMetric;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SystemMetricController extends Controller
{
    /*public function store(Request $request)
    {
        $validated = $request->validate([
            'chatbot_instance_id' => 'required|exists:chatbot_instances,id',
            'cpu_usage' => 'required|numeric',
            'ram_usage' => 'required|numeric',
            'disk_usage' => 'nullable|numeric',
            'uptime_seconds' => 'required|integer',
            'queue_size' => 'nullable|integer',
            'timestamp' => 'required|date',
        ]);

        $metric = SystemMetric::create($validated);

        return response()->json(['message' => 'System metrics stored', 'id' => $metric->id], 201);
    }*/
   /* public function storeMultiple(Request $request)
    {
        $data = $request->all();
        if (!is_array($data)) return response()->json(['error' => 'Array of system metrics expected.'], 422);

        $inserted = [];
        $errors = [];

        foreach ($data as $entry) {
            $validator = Validator::make($entry, [
                'chatbot_instance_id' => 'required|exists:chatbot_instances,id',
                'cpu_usage'           => 'required|integer',
                'ram_usage'           => 'required|integer',
                'disk_usage'          => 'required|integer',
                'uptime_seconds'      => 'required|integer',
                'queue_size'          => 'required|integer',
                'timestamp'           => 'required|date',
            ]);

            if ($validator->fails()) {
                $errors[] = $validator->errors();
                continue;
            }

            $inserted[] = SystemMetric::create($entry);
        }

        return response()->json([
            'stored' => count($inserted),
            'errors' => $errors,
        ], 201);
    }*/
   /* public function store(Request $request) //after migrate
    {
        $dataList = $request->input('data');

        if (!is_array($dataList)) {
            return response()->json(['error' => 'Invalid data payload'], 422);
        }

        $stored = 0;
        foreach ($dataList as $data) {
            $validated = Validator::make($data, [
                'chatbot_instance_id' => 'required|exists:chatbot_instances,id',
                'cpu_usage' => 'required|integer',
                'ram_usage' => 'required|integer',
                'disk_usage' => 'required|integer',
                'uptime_seconds' => 'required|integer',
                'queue_size' => 'required|integer',
                'timestamp' => 'required|date',
            ]);

            if ($validated->fails()) {
                Log::error(' SystemMetric Validation failed', $validated->errors()->toArray());
                continue;
            }

            SystemMetric::create($validated->validated());
            $stored++;
        }

        return response()->json(['message' => " Stored $stored system metric(s)"], 201);
    }*/
    public function store(Request $request)
    {
        $data = $request->input('data') ?? [$request->all()];

        $saved = [];

        foreach ($data as $metric) {
            /*$validated = validator($metric, [
               // 'chatbot_instance_id' => 'required|exists:chatbot_instances,id',
                'user_id' => $request->user()->id,
                'cpu_usage' => 'required|numeric',
                'ram_usage' => 'required|numeric',
                'disk_usage' => 'required|numeric',
                'uptime_seconds' => 'required|integer',
                'queue_size' => 'required|integer',
                'timestamp' => 'required|date',
            ])->validate();

            $saved[] = SystemMetric::create($validated);*/
            $validator = validator($metric, [
                'user_id' => 'required|integer',
                'cpu_usage'       => 'required|numeric',
                'ram_usage'       => 'required|numeric',
                'disk_usage'      => 'required|numeric',
                'uptime_seconds'  => 'required|integer',
                'queue_size'      => 'required|integer',
                'timestamp'       => 'required|date',
            ]);

            if ($validator->fails()) {
                Log::warning('SystemMetric validation failed', $validator->errors()->toArray());
                continue;
            }

            $validated = $validator->validated();
            $validated['user_id'] = $request->user()->id;

            $saved[] = SystemMetric::create($validated);
        }

        return response()->json([
            'message' => ' Stored',
            'count' => count($saved),
            'ids' => collect($saved)->pluck('id'),
        ], 201);
    }

}

