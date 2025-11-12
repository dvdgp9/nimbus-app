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
        Schema::create('shortlinks', function (Blueprint $table) {
            $table->id();
            
            // Relationship
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            
            // Link details
            $table->string('token', 64)->unique(); // Token firmado seguro
            $table->enum('action', ['confirm', 'cancel', 'reschedule']); // Acción del enlace
            $table->timestamp('expires_at'); // Fecha de expiración
            
            // Usage tracking
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->string('used_ip')->nullable();
            $table->string('used_user_agent')->nullable();
            
            $table->timestamps();
            
            $table->index('token');
            $table->index('appointment_id');
            $table->index('expires_at');
            $table->index('used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shortlinks');
    }
};
