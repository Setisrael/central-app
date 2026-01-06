<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChatbotInstance;
use App\Models\Module;
use App\Models\MetricUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class MetricUsageController extends Controller
{
    public function store(Request $request)
    {
        $data  = $request->input('data') ?? [$request->all()];
        $saved = [];

        // Module-Sync, falls mitgesendet
        if ($request->has('modules') && isset($data[0]['lookup']['chatbot_instance_id'])) {
            $chatbotId = $data[0]['lookup']['chatbot_instance_id'];
            $chatbot   = ChatbotInstance::find($chatbotId);

            if ($chatbot) {
                $moduleCodes = collect($request->modules)->map(function ($module) {
                    // Chatbot sendet ref_id (int) -> zentrale DB speichert es in code (int)
                    Module::firstOrCreate(
                        ['code' => $module['ref_id']],
                        ['name' => $module['name']]
                    );

                    return $module['ref_id'];
                });

                Log::info('Modules synced for chatbot', [
                    'chatbot_id'   => $chatbot->id,
                    'module_codes' => $moduleCodes->toArray(),
                ]);
            }
        }

        foreach ($data as $entry) {
            $lookup  = $entry['lookup'] ?? null;
            $payload = $entry['data'] ?? null;

            if (! $lookup || ! $payload) {
                Log::warning('Missing lookup or data structure in usage payload', [
                    'entry' => $entry,
                ]);
                continue;
            }

            $merged = array_merge($lookup, $payload);

            $validator = validator($merged, [
                'chatbot_instance_id' => 'required|integer|exists:chatbot_instances,id',
                'conversation_id'     => 'required|string',
                'message_id'          => 'required|integer',
                'agent_id'            => 'nullable|integer',
                'module_code'         => 'nullable|integer|exists:modules,code', // <--- zentrale modules.code
                'user_message'        => 'nullable|string',
                'agent_message'       => 'nullable|string',
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
                    'data'   => $merged,
                ]);
                continue;
            }

            $validated = $validator->validated();

            // ISO-8601 Timestamp -> Carbon -> MariaDB-kompatibel
            try {
                $validated['timestamp'] = Carbon::parse($validated['timestamp']);
            } catch (\Throwable $e) {
                Log::warning('MetricUsage timestamp parse failed, using now()', [
                    'original' => $merged['timestamp'] ?? null,
                    'error'    => $e->getMessage(),
                ]);
                $validated['timestamp'] = now();
            }

            // Schlüssel zum Identifizieren des Eintrags (für updateOrCreate)
            $attributes = [
                'chatbot_instance_id' => $validated['chatbot_instance_id'],
                'conversation_id'     => $validated['conversation_id'],
                'message_id'          => $validated['message_id'],
            ];

            // Werte, die gespeichert/aktualisiert werden (inkl. module_code, timestamp, etc.)
            $values = $validated;

            $saved[] = MetricUsage::updateOrCreate($attributes, $values);
        }

        return response()->json([
            'message' => 'Stored',
            'count'   => count($saved),
            'ids'     => collect($saved)->pluck('id'),
        ], 201);
    }
}
