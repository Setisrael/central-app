<?php

namespace App\Filament\Resources\ChatbotInstanceResource\Pages;

use App\Filament\Resources\ChatbotInstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChatbotInstances extends ListRecords
{
    protected static string $resource = ChatbotInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
