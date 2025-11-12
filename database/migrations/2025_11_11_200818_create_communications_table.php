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
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            
            // Communication details
            $table->enum('channel', ['email', 'sms', 'whatsapp']); // Canal usado
            $table->enum('type', ['reminder', 'confirmation', 'cancellation', 'reschedule']); // Tipo de mensaje
            $table->string('recipient'); // Email o teléfono
            $table->text('message_body'); // Contenido enviado
            $table->string('subject')->nullable(); // Solo para email
            
            // Status tracking
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'bounced'])->default('pending');
            $table->string('provider_message_id')->nullable(); // ID de Twilio/SendGrid
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // RGPD compliance
            $table->boolean('consent_verified')->default(false);
            
            $table->timestamps();
            
            $table->index('appointment_id');
            $table->index('patient_id');
            $table->index('channel');
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
