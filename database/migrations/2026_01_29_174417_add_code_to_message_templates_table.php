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
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('name');
            
            // Unique constraint: same user can't have two templates with same code for same channel
            $table->unique(['user_id', 'channel', 'code'], 'unique_user_channel_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropUnique('unique_user_channel_code');
            $table->dropColumn('code');
        });
    }
};
