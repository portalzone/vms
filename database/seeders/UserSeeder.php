<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'muojekevictor@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('abcd1234'),
            ]
        );
        $admin->assignRole('admin');

        // Manager
        $manager = User::firstOrCreate(
            ['email' => 'faith@gmail.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('abcd1234'),
            ]
        );
        $manager->assignRole('manager');

        // Driver
        $driver = User::firstOrCreate(
            ['email' => 'john@gmail.com'],
            [
                'name' => 'Driver User',
                'password' => Hash::make('abcd1234'),
            ]
        );
        $driver->assignRole('driver');

        // Gate Security
        $security = User::firstOrCreate(
            ['email' => 'mary@gmail.com'],
            [
                'name' => 'Gate Security User',
                'password' => Hash::make('abcd1234'),
            ]
        );
        $security->assignRole('gate_security');

        // Vehicle Owner
        $owner = User::firstOrCreate(
            ['email' => 'samuel@gmail.com'],
            [
                'name' => 'Vehicle Owner User',
                'password' => Hash::make('abcd1234'),
            ]
        );
        $owner->assignRole('vehicle_owner');
    }
}
