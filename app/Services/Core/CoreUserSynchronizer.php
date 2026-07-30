<?php

namespace App\Services\Core;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;

class CoreUserSynchronizer
{
    public function sync(array $coreUserPayload): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $coreUserPayload['email']],
            [
                'name' => $coreUserPayload['name'],
                'password' => User::query()
                    ->where('email', $coreUserPayload['email'])
                    ->value('password') ?? bcrypt(Str::password(32)),
            ],
        );

        if (!$user->employee_id) {
            $employeeQuery = Employee::query()->where('email', $coreUserPayload['email']);
            
            if (!empty($coreUserPayload['phone'])) {
                $employeeQuery->orWhere('phone', $coreUserPayload['phone']);
            }

            $employee = $employeeQuery->first();

            if ($employee) {
                $user->employee_id = $employee->id;
                $user->save();
            }
        }

        return $user;
    }
}
