<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@example.com',
                'username' => 'admin',
                'name' => 'Super Admin',
                'role' => 'super_admin',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'ga@example.com',
                'username' => 'adminga',
                'name' => 'General Affair',
                'role' => 'general_affair',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'admin2@example.com',
                'username' => 'admin2',
                'name' => 'Admin User',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'staff@example.com',
                'username' => 'staff',
                'name' => 'Staff User',
                'role' => 'staff',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData,
            );
        }

        // Fill missing roles & usernames for any pre-existing users without role
        User::whereNull('role')->orWhere('role', '')->get()->each(function (User $user): void {
            $username = $user->username;
            if (empty($username)) {
                $baseUsername = strtolower(explode('@', $user->email)[0] ?? 'user'.$user->id);
                $username = $baseUsername;
                $counter = 1;
                while (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                    $username = $baseUsername.$counter;
                    $counter++;
                }
            }

            $user->update([
                'username' => $username,
                'role' => 'general_affair',
                'password' => Hash::make('password'),
            ]);
        });
    }
}
