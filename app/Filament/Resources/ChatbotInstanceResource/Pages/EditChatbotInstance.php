<?php

namespace App\Filament\Resources\ChatbotInstanceResource\Pages;

use App\Filament\Resources\ChatbotInstanceResource;
use App\Models\ChatbotInstance;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Database\Eloquent\Model;

class EditChatbotInstance extends EditRecord
{
    protected static string $resource = ChatbotInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            // 🔁 Regenerate Token
            Actions\Action::make('regenerate_token')
                ->label('Regenerate Token')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    // Delete all existing tokens for this chatbot
                    PersonalAccessToken::where('tokenable_type', ChatbotInstance::class)
                        ->where('tokenable_id', $this->record->id)
                        ->delete();

                    // Create a new token
                    $token = $this->record->createToken($this->record->name)->plainTextToken;

                    Notification::make()
                        ->title('New Token Generated')
                        ->success()
                        ->persistent()
                        ->body("
                            🔁 <strong>New Token:</strong> <code>{$token}</code><br>
                            <small>All previous tokens for this chatbot have been revoked.</small>
                        ")
                        ->send();
                }),
        ];
    }

    // change chatbot name in personal_access_tokens aswell upon edit in GUI
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Update the chatbot
        $record->update($data);

        // Sync token name in personal_access_tokens table
        PersonalAccessToken::where('tokenable_type', ChatbotInstance::class)
            ->where('tokenable_id', $record->id)
            ->update(['name' => $record->name]);

        return $record;
    }

}
