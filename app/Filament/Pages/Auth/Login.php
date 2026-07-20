<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class Login extends BaseLogin
{
    /**
     * Get the credentials array from form data. Allows login via email or username.
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $loginType = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $loginType => $data['login'],
            'password' => $data['password'],
        ];
    }

    /**
     * Override the email form component to accept either email or username.
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label(__('Email atau Username'))
            ->required()
            ->autocomplete()
            ->autofocus();
    }
}
