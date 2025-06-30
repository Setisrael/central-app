<?php

namespace App\Http\Controllers\API;

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
            'module_code' => 'required|integer|min:1',
            'server_name' => 'required|max:255'
        ]);

        $existingUser = User::firstOrCreate(
            ['name' => $request->name, 'module_code' => $request->module_code],
            [
                'email' => Str::slug($request->name) . '@example.com',
                'password' => bcrypt(Str::random(32)),
                'is_chatbot' => true,
            ]
        );
        $existingToken = $existingUser->tokens()
            ->where('name', 'chatbot')
            ->first();

        $token = $existingUser->createToken('chatbot')->plainTextToken;
        ChatbotInstance::updateOrCreate(
            ['user_id' => $existingUser->id],
            [
                'name' => $request->name,
                'module_code' => $request->module_code,
                'server_name' => $request->server_name ?? 'localhost',
                'api_token' => $token,
            ]
        );
        return response()->json([
            'chatbot_instance_id' => $existingUser->id,
            'token' => $token,
        ]);
    }


}

