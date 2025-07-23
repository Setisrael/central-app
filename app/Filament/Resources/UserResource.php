<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->required()
                    ->email()
                    ->unique(ignoreRecord: true),

                /*TextInput::make('password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                    ->dehydrateStateUsing(fn ($state) => \Hash::make($state))
                    ->label('Password'),*/
                TextInput::make('password')
                    ->password()
                    ->label('Password')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? \Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn ($context) => $context === 'create'),

                Forms\Components\Toggle::make('is_admin'),

                Select::make('modules')
                    ->label('Taught Modules')
                    ->relationship('modules', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn () => auth()->user()?->is_admin),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\IconColumn::make('is_admin')->boolean(),
              //  TagsColumn::make('modules.name')->label('Modules'),
                TextColumn::make('modules.name')
                    ->label('Modules')
                   // ->listWithLineBreaks() // or ->bulleted()
                    ->badge()
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
    public static function shouldRegisterNavigation(): bool  // hide sidebar
    {
        return auth()->check() && auth()->user()->is_admin;
    }
    public static function canAccess(): bool    // hide page
    {
        return auth()->check() && auth()->user()->is_admin;
    }
    //added
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery(); // No chatbot filter needed anymore
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
