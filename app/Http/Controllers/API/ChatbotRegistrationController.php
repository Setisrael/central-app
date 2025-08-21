<?php

namespace App\Http\Controllers\API;

use App\Models\Module;
use App\Models\User;
use App\Models\ChatbotInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class ChatbotRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'server_name' => 'required|max:255',
// added when trying to add modules to payload
            'modules' => 'nullable|array',
            'modules.*.ref_id' => 'required_with:modules|integer',
            'modules.*.name' => 'required_with:modules|string',
        ]);

        $chatbot = ChatbotInstance::firstOrCreate(
            ['name' => $request->name],
            [
                'server_name' => $request->server_name ?? 'localhost',
            ]
        );

        // FIXED: Sync using module codes instead of module IDs
        if ($request->has('modules')) {
            $moduleCodes = collect($request->modules)->map(function ($module) {
                // Create or update the module
                Module::firstOrCreate(
                    ['code' => $module['ref_id']],
                    ['name' => $module['name']]
                );

                // Return the code (not the ID) for syncing
                return $module['ref_id'];
            });
            $chatbot->modules()->sync($moduleCodes);

            Log::info('Modules synced for chatbot registration', [
                'chatbot_id' => $chatbot->id,
                'module_codes' => $moduleCodes->toArray()
            ]);
           }

            $token = $chatbot->createToken($request->name)->plainTextToken;

            return response()->json([
                'chatbot_instance_id' => $chatbot->id,
                'token' => $token,
            ]);
    }



}

