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
        Schema::table('metric_usages', function (Blueprint $table) {
            $table->text('user_message')->nullable()->after('module_code');
            $table->text('agent_message')->nullable()->after('user_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metric_usages', function (Blueprint $table) {
            $table->dropColumn(['user_message', 'agent_message']);
        });
    }
};
