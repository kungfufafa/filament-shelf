<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\SimplePage;

class SsoLogin extends SimplePage
{
    protected string $view = 'filament.auth.sso-login';
    
    public function mount()
    {
        redirect()->route('sso.redirect')->send();
    }
}
