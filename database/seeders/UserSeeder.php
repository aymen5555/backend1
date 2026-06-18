<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = 'aymencharf55@gmail.com';

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'first_name'        => 'Aymen',
                'last_name'         => 'Charfeddine',
                'phone'             => '+216 XX XXX XXX',
                'password'          => Hash::make('Admin@1234'),
                'role'              => 'super_admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        // Create a demo CLIENT account for testing
        User::firstOrCreate(
            ['email' => 'client@playspace.tn'],
            [
                'first_name'        => 'Test',
                'last_name'         => 'Client',
                'phone'             => '+216 99 999 999',
                'password'          => Hash::make('Client@1234'),
                'role'              => 'client',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
