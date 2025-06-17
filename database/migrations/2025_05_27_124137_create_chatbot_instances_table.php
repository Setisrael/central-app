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
        Schema::create('chatbot_instances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('module_code');
            $table->string('server_name')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('api_token')->unique();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_instances');
    }
};
