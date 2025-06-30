<?php

namespace App\Filament\Resources\ChatbotInstanceResource\Pages;

use App\Filament\Resources\ChatbotInstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChatbotInstance extends EditRecord
{
    protected static string $resource = ChatbotInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
