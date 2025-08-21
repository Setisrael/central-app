<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChatbotInstance;
use App\Models\Module;
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
// added when trying to add modules to payload

        if ($request->has('modules') && isset($data[0]['chatbot_instance_id'])) {
            $chatbot = ChatbotInstance::find($data[0]['chatbot_instance_id']);

            if ($chatbot) {
                $moduleCodes = collect($request->modules)->map(function ($module) {
                    // Create or update the module
                    Module::firstOrCreate(
                        ['code' => $module['ref_id']],
                        ['name' => $module['name']]
                    );

                    // Return the code for syncing
                    return $module['ref_id'];
                });

                Log::info('Modules synced for chatbot', [
                    'chatbot_id' => $chatbot->id,
                    'module_codes' => $moduleCodes->toArray()
                ]);
            }
        }// ends here
        foreach ($data as $entry) {
            $lookup = $entry['lookup'] ?? null;
            $payload = $entry['data'] ?? null;

            if (!$lookup || !$payload) {
                Log::warning('Missing lookup or data structure in usage payload');
                continue;
            }

            $validator = validator(array_merge($lookup, $payload), [
                'chatbot_instance_id' => 'required|integer|exists:chatbot_instances,id',
                'conversation_id'     => 'required|string',
                'message_id'          => 'required|integer',
                'agent_id'            => 'nullable|integer',
                //'module_id'           => 'nullable|integer|exists:modules,code',
                'module_code'         => 'nullable|integer|exists:modules,code',
                'embedding_id'        => 'nullable|string',
                'document_id'         => 'nullable|integer',
                'student_id_hash'     => 'required|string',
                'prompt_tokens'       => 'required|integer',
                'completion_tokens'   => 'required|integer',
                'temperature'         => 'nullable|numeric',
                'model'               => 'required|string',
                'latency_ms'          => 'nullable|integer',
                'duration_ms'         => 'nullable|integer',
                'status'              => 'required|string|in:ok,error,timeout,empty',
                'answer_type'         => 'required|string|in:embedding,llm,both,none',
                'helpful'             => 'nullable|boolean',
                'source'              => 'nullable|string',
                'chatbot_version'     => 'nullable|string',
                'timestamp'           => 'required|date',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed for metric', [
                    'errors' => $validator->errors()->toArray(),
                    'data' => array_merge($lookup, $payload)
                ]);
                continue; // Continue processing other entries instead of returning error
            }

            $validated = $validator->validated();

            $saved[] = MetricUsage::updateOrCreate(
                [
                    'chatbot_instance_id' => $lookup['chatbot_instance_id'],
                    'conversation_id'     => $lookup['conversation_id'],
                    'message_id'          => $lookup['message_id'],
                ],
                $payload
            );
        }

        return response()->json([
            'message' => ' Stored',
            'count' => count($saved),
            'ids' => collect($saved)->pluck('id'),
        ], 201);
    }
}
