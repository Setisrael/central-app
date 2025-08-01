<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatbotInstanceResource\Pages;
use App\Filament\Resources\ChatbotInstanceResource\RelationManagers;
use App\Models\ChatbotInstance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChatbotInstanceResource extends Resource
{
    protected static ?string $model = ChatbotInstance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('server_name')->required(),
               // Forms\Components\TextInput::make('api_token')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('server_name')->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatbotInstances::route('/'),
            'create' => Pages\CreateChatbotInstance::route('/create'),
            'edit' => Pages\EditChatbotInstance::route('/{record}/edit'),
        ];
    }
    public static function canAccess(): bool    // hide page
    {
        return auth()->check() && auth()->user()->is_admin;
    }
}
