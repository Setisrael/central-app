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
        Schema::create('chatbot_instance_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_instance_id')->constrained()->cascadeOnDelete();
            //$table->foreignId('module_code')->constrained()->cascadeOnDelete();
            // CORRECTED: Use unsignedBigInteger and explicit foreign key
            $table->unsignedBigInteger('module_code');
            $table->foreign('module_code')->references('code')->on('modules')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['chatbot_instance_id', 'module_code']); // Prevent duplicates
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_instance_module');
    }
};
