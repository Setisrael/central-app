<?php

namespace App\Filament\Resources\ChatbotInstanceResource\Pages;

use App\Filament\Resources\ChatbotInstanceResource;
use App\Models\Module;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class CreateChatbotInstance extends CreateRecord
{
    protected static string $resource = ChatbotInstanceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Create the chatbot instance
        $record = static::getModel()::create($data);

        // Create a Sanctum token for this instance
        $token = $record->createToken($record->name)->plainTextToken;

        // Show token + ID as a persistent notification
        Notification::make()
            ->title('Chatbot Registered')
            ->success()
            ->persistent()
            ->body("
                🆔 <strong>Instance ID:</strong> {$record->id}<br>
                🔑 <strong>Token:</strong> <code>{$token}</code><br>
                <small>Copy this token into the chatbot config — it will only be shown once.</small>
            ")
            ->send();

        return $record;
    }
}
