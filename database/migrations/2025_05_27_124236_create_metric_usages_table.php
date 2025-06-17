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
        Schema::create('metric_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_instance_id')->constrained('chatbot_instances')->onDelete('cascade');

            $table->string('conversation_id')->nullable();
            $table->string('embedding_id')->nullable();

            $table->integer('prompt_tokens');
            $table->integer('completion_tokens');
            $table->float('temperature');
            $table->string('model');
            $table->integer('latency_ms');
            $table->string('status')->default('ok');
            $table->string('student_id_hash')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('timestamp');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metric_usages');
    }
};
