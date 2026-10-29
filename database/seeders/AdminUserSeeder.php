<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the administrator account.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => config('claims.admin.email')],
            [
                'name' => config('claims.admin.name'),
                'first_name' => config('claims.admin.first_name'),
                'last_name' => config('claims.admin.last_name'),
                'designation' => config('claims.admin.designation'),
                'password' => Hash::make(config('claims.admin.password')),
                'email_verified_at' => now(),
            ],
        );
    }
}
