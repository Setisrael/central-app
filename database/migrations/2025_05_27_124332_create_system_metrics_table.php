<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_instance_id')->constrained('chatbot_instances')->onDelete('cascade');

            $table->float('cpu_usage');
            $table->float('ram_usage');
            $table->float('disk_usage')->nullable();
            $table->integer('uptime_seconds');
            $table->integer('queue_size')->nullable();
            $table->timestamp('timestamp');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_metrics');
    }
};
