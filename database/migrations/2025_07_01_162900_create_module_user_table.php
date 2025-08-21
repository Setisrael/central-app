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
        Schema::create('module_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            //$table->foreignId('module_code')->constrained()->cascadeOnDelete();
            // CORRECTED: Use unsignedBigInteger and explicit foreign key
            $table->unsignedBigInteger('module_code');
            $table->foreign('module_code')->references('code')->on('modules')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'module_code']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_user');
    }
};
