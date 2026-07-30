<?php

namespace App\Console\Commands;

use App\Services\Core\CoreAuthService;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncCorePermissionsCommand extends Command
{
    protected $signature = 'core:sync-permissions';

    protected $description = 'Sync locally generated Shield permissions to the Core API';

    public function handle(CoreAuthService $coreAuthService): int
    {
        $permissions = \Spatie\Permission\Models\Permission::all()
            ->map(function ($p) {
                return [
                    'permission' => $p->name,
                    'label' => Str::headline($p->name),
                ];
            })
            ->toArray();

        if (empty($permissions)) {
            $this->warn('No permissions found to sync.');
            return self::SUCCESS;
        }

        $this->info('Syncing ' . count($permissions) . ' permissions to Core...');

        // For a machine-to-machine request, we need a service token. 
        // We will assume the core URL is configured, but we might not have a standard "login" token.
        // For simplicity, we can use an environment variable CORE_SERVICE_TOKEN.
        $token = config('core.service_token');
        $systemCode = config('core.system_code');

        if (!$token || !$systemCode) {
            $this->error('CORE_SERVICE_TOKEN or CORE_SYSTEM_CODE is missing in configuration.');
            return self::FAILURE;
        }

        try {
            $response = $coreAuthService->registerPermissions($token, $systemCode, $permissions);
            $this->info('Successfully synced ' . ($response['synced'] ?? 0) . ' permissions to Core.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to sync to core: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
