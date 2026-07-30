<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\Http;

class CoreAuthService
{
    public function login(string $email, string $password): array
    {
        return Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->post('/api/auth/login', [
                'email' => $email,
                'password' => $password,
            ])
            ->throw()
            ->json();
    }

    public function requestOtp(string $phone): array
    {
        // Does not throw so caller can handle 429 cooldowns natively
        return Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->post('/api/auth/otp/request', [
                'phone' => $phone,
            ])
            ->json();
    }

    public function verifyOtp(string $phone, string $otp): array
    {
        return Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->post('/api/auth/otp/verify', [
                'phone' => $phone,
                'otp' => $otp,
            ])
            ->throw()
            ->json();
    }

    public function getCurrentUser(string $token): array
    {
        return $this->authorizedRequest($token)
            ->get('/api/me')
            ->throw()
            ->json();
    }

    public function checkSystemAccess(string $token, string $systemCode): array
    {
        return $this->authorizedRequest($token)
            ->post('/api/system-access/check', [
                'system_code' => $systemCode,
            ])
            ->throw()
            ->json();
    }

    public function registerPermissions(string $token, string $systemCode, array $permissions): array
    {
        return $this->authorizedRequest($token)
            ->post("/api/systems/{$systemCode}/permissions", [
                'permissions' => $permissions,
            ])
            ->throw()
            ->json();
    }

    protected function authorizedRequest(string $token)
    {
        return Http::baseUrl($this->baseUrl())
            ->timeout($this->timeout())
            ->acceptJson()
            ->withToken($token);
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('core.base_url'), '/');
    }

    protected function timeout(): int
    {
        return (int) config('core.timeout', 10);
    }
}
