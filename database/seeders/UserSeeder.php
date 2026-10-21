<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => bcrypt('123456'), // Always use bcrypt to hash passwords
                'email' => 'admin@gmail.com',
                'type' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ZB Admin',
                'username' => 'zb_admin',
                'password' => bcrypt('123456'), // Always use bcrypt to hash passwords
                'email' => 'zestbrainsphp@gmail.com',
                'type' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert users into the database
        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }
    }
}
