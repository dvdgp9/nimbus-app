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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable(); // Formato: +34XXXXXXXXX
            $table->enum('preferred_channel', ['email', 'sms', 'whatsapp'])->default('email');
            $table->boolean('consent_email')->default(false);
            $table->boolean('consent_sms')->default(false);
            $table->boolean('consent_whatsapp')->default(false);
            $table->timestamp('consent_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('email');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
