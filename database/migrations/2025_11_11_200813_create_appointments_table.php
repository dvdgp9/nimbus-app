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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            
            // Google Calendar data
            $table->string('google_event_id')->unique();
            $table->string('calendar_id');
            $table->string('summary'); // Título del evento
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('timezone')->default('Europe/Madrid');
            $table->string('hangout_link')->nullable();
            
            // Patient relationship
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            
            // Nimbus status workflow
            $table->enum('nimbus_status', [
                'pending',      // Recién sincronizado
                'reminder_sent', // Recordatorio enviado
                'confirmed',    // Paciente confirmó
                'cancelled',    // Paciente canceló
                'rescheduled',  // Paciente quiere reprogramar
                'completed'     // Cita finalizada
            ])->default('pending');
            
            // Tracking
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            
            // Raw data from Google
            $table->json('raw_payload')->nullable();
            
            $table->timestamps();
            
            $table->index('google_event_id');
            $table->index('calendar_id');
            $table->index('start_at');
            $table->index('nimbus_status');
            $table->index('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
