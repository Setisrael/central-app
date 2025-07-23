<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   /* public function up(): void
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

    }*/
    public function up(): void
    {
        Schema::create('metric_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chatbot_instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable();
            $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('message_id')->nullable();

            $table->uuid('embedding_id')->nullable();
            $table->foreignId('document_id')->nullable();

            $table->string('student_id_hash'); // anonymized ID

            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->float('temperature')->nullable();
            $table->string('model')->nullable();

            $table->unsignedInteger('latency_ms')->nullable();  // Time from user message to first token
            $table->unsignedInteger('duration_ms')->nullable(); // Full convo duration

            $table->enum('status', ['ok', 'error', 'timeout', 'empty'])->default('ok');
            $table->enum('answer_type', ['embedding', 'llm', 'both', 'none'])->default('llm');
            $table->boolean('helpful')->nullable(); // future: from feedback

            $table->string('source')->nullable(); // e.g. 'ilias', 'cli', 'api'
            $table->string('chatbot_version')->nullable(); // e.g. commit hash

            $table->timestamp('timestamp'); // original timestamp from chatbot
            $table->timestamps();           // created_at = when saved on central
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
