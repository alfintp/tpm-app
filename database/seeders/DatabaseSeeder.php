<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users - sesuai dengan data yang ada di database
        // Password default untuk semua user: "password"

        User::create([
            'full_name' => 'Administrator',
            'email' => 'admin@tpm.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'city' => 'both',
        ]);

        User::create([
            'full_name' => 'Ucup',
            'email' => 'ucup@lsi.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'city' => 'both',
        ]);

        User::create([
            'full_name' => 'Agus',
            'email' => 'agus@lsi.com',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'city' => 'pasuruan',
        ]);
        // user bahrul
        User::create([
            'full_name' => 'Bahrul',
            'email' => 'bahrul@lsi.com',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'city' => 'sby',
        ]);
        // user hariyono
        User::create([
            'full_name' => 'Hariyono',
            'email' => 'hariyono@lsi.com',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'city' => 'pasuruan',
        ]);
        // user irwan
        User::create([
            'full_name' => 'Irwan',
            'email' => 'irwan@lsi.com',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'city' => 'pasuruan',
        ]);
        // user surya
        User::create([
            'full_name' => 'Surya',
            'email' => 'surya@lsi.com',
            'password' => bcrypt('password'),
            'role' => 'technician',
            'city' => 'sby',
        ]);


    }
}
