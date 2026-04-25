<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');           // ex: login, logout, shift.start, intervention.create
            $table->string('entity_type')->nullable(); // ex: shift, intervention, item, user
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable(); // données supplémentaires
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('level', ['info', 'warning', 'danger'])->default('info');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};