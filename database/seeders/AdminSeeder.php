<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin Test',
            'email' => 'admin@pharmagarde.tg',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+22890000000',
        ]);
    }
}
