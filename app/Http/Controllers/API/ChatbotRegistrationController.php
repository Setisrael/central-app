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
            ['name' => $request->name ],
            [
                'server_name' => $request->server_name ?? 'localhost',
            ]
        );

        // added when trying to add modules to payload
        if ($request->has('modules')) {
            $moduleIds = collect($request->modules)->map(function ($module) {
                return Module::firstOrCreate(
                    ['code' => $module['ref_id']],
                    ['name' => $module['name']]
                )->id;
            });

            $chatbot->modules()->sync($moduleIds);
        }  // ends here

        $token = $chatbot->createToken($request->name)->plainTextToken;

        return response()->json([
            'chatbot_instance_id' => $chatbot->id,
            'token' => $token,
        ]);
    }


}

