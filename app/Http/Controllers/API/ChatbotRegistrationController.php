<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;


class ChatbotRegistrationController extends Controller
{
    /*public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => 'chatbot_' . Str::random(10) . '@example.com',
            'password' => bcrypt(Str::random(32)), // not used for login
            'is_chatbot' => true, // optional boolean if you want to tag them
        ]);

        $token = $user->createToken('chatbot')->plainTextToken;

        return response()->json([
            'chatbot_instance_id' => $user->id,
            'token' => $token,
        ]);
    }*/
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'module_code' => 'required|integer|min:1'
        ]);

        $existingUser = User::firstOrCreate(
            ['name' => $request->name, 'module_code' => $request->module_code],
            [
                'email' => 'chatbot_' . Str::slug($request->name) . '@example.com',
                'password' => bcrypt(Str::random(32)),
                'is_chatbot' => true,
            ]
        );
        $existingToken = $existingUser->tokens()
            ->where('name', 'chatbot')
            ->first();

       /* $existingToken = $existingUser->tokens()
            ->where('name', 'chatbot')
            ->first();

        if ($existingToken) {
            $token = $existingToken->plainTextToken ?? $existingToken->accessToken;
        } else {
            $token = $existingUser->createToken('chatbot')->plainTextToken;
        }*/
        $token = $existingUser->createToken('chatbot')->plainTextToken;

        return response()->json([
            'chatbot_instance_id' => $existingUser->id,
            'token' => $token,
        ]);
    }


}

