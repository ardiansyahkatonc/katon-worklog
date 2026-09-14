<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'User A',
            'email' => 'usera@example.com',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'User B',
            'email' => 'userb@example.com',
            'password' => Hash::make('password'),
        ]);

        Category::create(['name' => 'Pekerjaan']);
        Category::create(['name' => 'Pribadi']);
        Category::create(['name' => 'Urgent']);
    }
}
