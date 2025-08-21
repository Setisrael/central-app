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

            $table->foreignId('chatbot_instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable();
           // $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
            // CORRECTED: Use unsignedBigInteger since we're referencing modules.code (not modules.id)
            $table->unsignedBigInteger('module_code')->nullable();
            // CORRECTED: Explicitly define the foreign key relationship
            $table->foreign('module_code')->references('code')->on('modules')->nullOnDelete();


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
