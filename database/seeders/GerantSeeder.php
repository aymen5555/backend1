<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class GerantSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'gerant@playspace.tn';

        $user = User::firstWhere('email', $email);
        if (!$user) {
            $user = User::create([
                'first_name' => 'Gerant',
                'last_name'  => 'Test',
                'email'      => $email,
                'password'   => Hash::make('Gerant@1234'),
                'role'       => 'gerant',
                'phone'      => '+21600000002',
                'email_verified_at' => now(),
                'is_active'  => true,
            ]);
        }

        // assign complexe id 1 to this gerant if complexe exists
        if (DB::table('complexes')->where('id', 1)->exists()) {
            DB::table('complexes')->where('id', 1)->update(['owner_id' => $user->id]);
        }

        $emailAhmed = 'ahmed@example.com';

        $ahmed = User::firstWhere('email', $emailAhmed);
        if (!$ahmed) {
            $ahmed = User::create([
                'first_name' => 'Ahmed',
                'last_name'  => 'Demo',
                'email'      => $emailAhmed,
                'password'   => Hash::make('SecurePass123'),
                'role'       => 'gerant',
                'phone'      => '+21600000003',
                'email_verified_at' => now(),
                'is_active'  => true,
            ]);
        }

        // Do not assign a complexe automatically if none are available.
        // This lets Ahmed log in as a gerant and see the dashboard guard message.
    }
}
