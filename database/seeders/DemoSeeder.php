<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ChatbotInstance;
use App\Models\SystemMetric;
use App\Models\MetricUsage;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'admin',
                'email' => 'admin@example.com',
                'password' => '$2y$12$1Z5fr4Ws2LUY6Gr43MhfW.jjcdlqHBg9ibQ18i3N4Usr4nlhoXICC', // password is password
                'is_admin' => true,
            ],

            ['email' => 'israel.metinou@alumni.fh-aachen.de'],
            [
                'name' => 'Metinou',
                'email' => 'israel.metinou@alumni.fh-aachen.de',
                'password' => '$2y$12$1Z5fr4Ws2LUY6Gr43MhfW.jjcdlqHBg9ibQ18i3N4Usr4nlhoXICC', // password is password
                'is_admin' => true,
            ]
        );


    }
}
