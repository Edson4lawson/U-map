<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'username' => 'admin',
            'password' => 'umapAdmin2026!', // Sera hashé automatiquement par le mutator
        ]);
    }
}
