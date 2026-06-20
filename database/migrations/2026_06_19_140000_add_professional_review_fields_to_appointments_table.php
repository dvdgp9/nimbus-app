<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('google_color_id', 10)->nullable()->after('message_code');
            $table->timestamp('professional_review_notified_at')->nullable()->after('unknown_patient_notified');
            $table->timestamp('professional_reviewed_at')->nullable()->after('professional_review_notified_at');
            $table->string('professional_review_decision', 20)->nullable()->after('professional_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'google_color_id',
                'professional_review_notified_at',
                'professional_reviewed_at',
                'professional_review_decision',
            ]);
        });
    }
};
