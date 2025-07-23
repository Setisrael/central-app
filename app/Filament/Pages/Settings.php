<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Settings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.pages.settings';
    protected static ?string $title = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 99;

    public ?array $passwordData = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Change Password')
                    ->description('Update your account password')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (!Hash::check($value, auth()->user()->password)) {
                                            $fail('The current password is incorrect.');
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rules([Password::min(8)->letters()->numbers()])
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                if ($get('password_confirmation') !== null) {
                                    $this->validateOnly('passwordData.password_confirmation');
                                }
                            }),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rules([
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if ($value !== $get('password')) {
                                            $fail('The password confirmation does not match.');
                                        }
                                    };
                                },
                            ]),
                    ])
                    ->columns(1),
            ])
            ->statePath('passwordData');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('updatePassword')
                ->label('Update Password')
                ->action('updatePassword')
                ->color('primary'),
        ];
    }

    public function updatePassword(): void
    {
        $data = $this->form->getState();

        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        // Clear the form
        $this->passwordData = [];
        $this->form->fill();

        Notification::make()
            ->title('Password updated successfully')
            ->success()
            ->send();
    }
}
