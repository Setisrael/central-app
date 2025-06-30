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
                'is_chatbot' => false,
                'module_code' => 0,
                'is_admin' => true,
            ]
        );
        $chatbots = [
            [2, 'Chatbot 1', 'chatbot_chatbot-1@example.com', '56789'],
            [3, 'Chatbot 2', 'chatbot-2@example.com', '60789'],
            [4, 'Chatbot 3', 'chatbot-3@example.com', '80789'],
        ];

        foreach ($chatbots as [$id, $name, $email, $moduleCode]) {
            User::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt('secret'),
                    'is_chatbot' => true,
                    'module_code' => $moduleCode,
                    'is_admin' => false,
                ]
            );
        }

        // Create chatbot instances
        ChatbotInstance::upsert([
            [
                'id' => 1,
                'name' => 'Chatbot 1',
                'module_code' => '56789',
                'server_name' => 'localhost',
                'user_id' => 2,
                'api_token' => '1|eHW3B6FDCTL2Mona3vdzozfPURxP6N57N2krl1rmfb8b5dbf',
            ],
            [
                'id' => 2,
                'name' => 'Chatbot 2',
                'module_code' => '60789',
                'server_name' => 'localhost',
                'user_id' => 3,
                'api_token' => '2|1B4aOrFvuAryw4nKgsMzdvKWoH16lYuAxvPFnI74e8422ec8',
            ],
            [
                'id' => 3,
                'name' => 'Chatbot 3',
                'module_code' => '80789',
                'server_name' => 'LAPTOP-VLQEEDSP',
                'user_id' => 4,
                'api_token' => '3|K5ppNlwSqepl1XWNVjipKSvpIEZXauGeq4b2xPuV49b84152',
            ],
        ], ['id'], ['name', 'module_code', 'server_name', 'user_id', 'api_token']);

        // Seed system metrics
        $systemMetrics = [
            [9, 48, 84.69, 0, 0, '2025-06-30 00:04:46', 2],
            [11, 50, 84.69, 0, 0, '2025-06-30 00:05:31', 2],
            [9, 50, 84.69, 0, 0, '2025-06-30 00:17:48', 3],
            [18, 52, 83.18, 0, 0, '2025-06-30 05:18:47', 4],
            [10, 50, 83.18, 0, 0, '2025-06-30 05:21:28', 4],
        ];

        foreach ($systemMetrics as [$cpu, $ram, $disk, $uptime, $queue, $timestamp, $userId]) {
            SystemMetric::create([
                'cpu_usage' => $cpu,
                'ram_usage' => $ram,
                'disk_usage' => $disk,
                'uptime_seconds' => $uptime,
                'queue_size' => $queue,
                'timestamp' => $timestamp,
                'user_id' => $userId,
            ]);
        }

        $studentId = 'student123';
        $studentHash = hash('sha256', $studentId);

        foreach ([2, 3, 4] as $userId) {
            for ($i = 0; $i < 10; $i++) {
                $createdAt = now()->subDays(rand(0, 7))->subMinutes(rand(0, 60));

                DB::table('metric_usages')->insert([
                    'conversation_id' => 'conv_' . rand(1000, 9999),
                    'embedding_id' => 'embed_' . rand(1000, 9999),
                    'prompt_tokens' => rand(80, 300),
                    'completion_tokens' => rand(50, 300),
                    'temperature' => 0.7,
                    'model' => 'gpt-4o-mini',
                    'latency_ms' => rand(0, 1500),
                    'status' => 'ok',
                    'student_id_hash' => $studentHash,
                    'duration_ms' => rand(1000, 15000),
                    'timestamp' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'user_id' => $userId,
                ]);
            }
        }
    }
}
