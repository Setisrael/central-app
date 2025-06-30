<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MetricUsage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;



class MetricUsageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->input('data') ?? [$request->all()];



        $saved = [];

        foreach ($data as $metric) {
            $validator = validator($metric, [
                'user_id' => 'required|integer',
                'conversation_id'     => 'required|string',
                'embedding_id'        => 'nullable|string',
                'prompt_tokens'       => 'required|integer',
                'completion_tokens'   => 'required|integer',
                'temperature'         => 'required|numeric',
                'model'               => 'required|string',
                'latency_ms'          => 'required|integer',
                'status'              => 'required|string',
                'student_id_hash'     => 'required|string',
                'duration_ms'         => 'required|integer',
                'timestamp'           => 'required|date',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed for metric', $validator->errors()->toArray());
                continue;
            }

            $validated = $validator->validated();
            $validated['user_id'] = $request->user()->id;

            $saved[] = MetricUsage::create($validated);
        }

        return response()->json([
            'message' => ' Stored',
            'count' => count($saved),
            'ids' => collect($saved)->pluck('id'),
        ], 201);
    }
    /* public function store(Request $request) // after migrate method
     {
         $dataList = $request->input('data');

         if (!is_array($dataList)) {
             return response()->json(['error' => 'Invalid data payload'], 422);
         }

         $stored = 0;
         foreach ($dataList as $data) {
             $validated = Validator::make($data, [
                 'chatbot_instance_id' => 'required|exists:chatbot_instances,id',
                 'conversation_id' => 'nullable|string',
                 'embedding_id' => 'nullable|string',
                 'prompt_tokens' => 'required|integer',
                 'completion_tokens' => 'required|integer',
                 'temperature' => 'nullable|numeric',
                 'model' => 'required|string',
                 'latency_ms' => 'nullable|integer',
                 'status' => 'required|string',
                 'student_id_hash' => 'required|string',
                 'duration_ms' => 'nullable|integer',
                 'timestamp' => 'required|date',
             ]);

             if ($validated->fails()) {
                 Log::error(' MetricUsage Validation failed', $validated->errors()->toArray());
                 continue;
             }

             MetricUsage::create($validated->validated());
             $stored++;
         }

         return response()->json(['message' => " Stored $stored metric(s)"], 201);
     }*/
   /* public function storeMultiple(Request $request)
    {
        $data = $request->all();
        if (!is_array($data)) return response()->json(['error' => 'Array of usage metrics expected.'], 422);

        $inserted = [];
        $errors = [];

        foreach ($data as $entry) {
            $validator = Validator::make($entry, [
                'chatbot_instance_id' => 'required|exists:chatbot_instances,id',
                'conversation_id'     => 'nullable|string',
                'embedding_id'        => 'nullable|string',
                'prompt_tokens'       => 'required|integer',
                'completion_tokens'   => 'required|integer',
                'temperature'         => 'required|numeric',
                'model'               => 'required|string',
                'latency_ms'          => 'required|integer',
                'status'              => 'required|string',
                'student_id_hash'     => 'nullable|string',
                'duration_ms'         => 'required|integer',
                'timestamp'           => 'required|date',
            ]);

            if ($validator->fails()) {
                $errors[] = $validator->errors();
                continue;
            }

            $inserted[] = MetricUsage::create($entry);
        }

        return response()->json([
            'stored' => count($inserted),
            'errors' => $errors,
        ], 201);
    }*/

   /* public function store(Request $request)
    {
        try { $validated = $request->validate([
            'chatbot_instance_id' => 'required|exists:chatbot_instances,id',
            'conversation_id' => 'required|string|max:255',
            'embedding_id' => 'nullable|string|max:255',
            'prompt_tokens' => 'required|integer',
            'completion_tokens' => 'required|integer',
            'temperature' => 'required|numeric',
            'model' => 'required|string',
            'latency_ms' => 'required|integer',
            'status' => 'required|string',
            'student_id_hash' => 'required|string',
            'duration_ms' => 'required|integer',
            'timestamp' => 'required|date',
        ]);


            $metric = MetricUsage::create($validated);
            return response()->json(['message' => ' Stored', 'id' => $metric->id], 201);
        } /*catch (\Throwable $e) {
            Log::error(' Failed to store MetricUsage: ' . $e->getMessage(), ['data' => $validated]);
            return response()->json(['error' => 'Failed to store'], 500);
        }*/
       /* catch (\Illuminate\Validation\ValidationException $e) {
            Log::error(' MetricUsage Validation failed', $e->errors());
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        }
    }*/
    /* public function store(Request $request)
    {
        $validated = $request->validate([
            'chatbot_instance_id' => 'required|exists:chatbot_instances,id',
            'conversation_id' => 'nullable|string',
            'embedding_id' => 'nullable|string',
            'prompt_tokens' => 'required|integer',
            'completion_tokens' => 'required|integer',
            'temperature' => 'required|numeric',
            'model' => 'required|string',
            'latency_ms' => 'required|integer',
            'status' => 'required|string',
            'student_id_hash' => 'nullable|string',
            'duration_ms' => 'nullable|integer',
            'timestamp' => 'required|date',
        ]);

        $metric = MetricUsage::create($validated);

        return response()->json(['message' => 'Metric stored', 'id' => $metric->id], 201);
    }*/

    /*public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'chatbot_instance_id' => 'required|exists:chatbot_instances,id',
                'conversation_id' => 'required|string',
                'embedding_id' => 'required|string',
                'prompt_tokens' => 'required|integer',
                'completion_tokens' => 'required|integer',
                'temperature' => 'required|numeric',
                'model' => 'required|string',
                'latency_ms' => 'required|integer',
                'status' => 'required|string',
                'student_id_hash' => 'required|string',
                'duration_ms' => 'required|integer',
                'timestamp' => 'required|date',
            ]);

            Log::info(' Valid metric payload received', $validated);

            return response()->json(['message' => 'Metric usage stored (mocked)']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error(' Validation failed:', $e->errors());

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }*/

}
