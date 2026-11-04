<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => '研修 太郎',
            'email' => 'test@example.com',
            // パスワードはFactoryの初期値 "password"
        ]);

        \App\Models\Task::factory()->count(15)->for($user)->create();
        \App\Models\Task::factory()->count(5)->create(); // 他人のタスク
    }
}
