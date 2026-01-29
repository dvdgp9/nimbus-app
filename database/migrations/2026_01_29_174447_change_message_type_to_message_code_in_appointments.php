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
        Schema::table('appointments', function (Blueprint $table) {
            // Drop old integer column
            $table->dropColumn('message_type');
        });

        Schema::table('appointments', function (Blueprint $table) {
            // Add new string column for message code (extracted from event title)
            $table->string('message_code', 20)->nullable()->after('patient_id')
                ->comment('Código de mensaje extraído del título del evento (ej: BP, RC, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('message_code');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->integer('message_type')->nullable()->after('patient_id')
                ->comment('Tipo de mensaje: 1=no pagada, 2=pagada, 3=bono nuevo, 4=primera sesión');
        });
    }
};
