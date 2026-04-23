<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['respi', 'cardio', 'trauma', 'neuro', 'pedia', 'general']);
            $table->enum('patient_gender', ['male', 'female']);
            $table->tinyInteger('patient_age')->unsigned();
            $table->json('gestures')->nullable();
            $table->enum('driving', ['outbound', 'return', 'round_trip', 'none'])->default('none');
            $table->boolean('no_transport')->default(false);
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};