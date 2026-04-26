<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#4A5568');
            $table->string('bg')->default('#F0F2F5');
            $table->boolean('active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Insérer les catégories par défaut
        DB::table('intervention_categories')->insert([
            ['name' => 'Cardio',      'color' => '#C0392B', 'bg' => '#FEF2F2', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Respi',       'color' => '#2E86C1', 'bg' => '#E3F0FA', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trauma',      'color' => '#D4860B', 'bg' => '#FBF1E0', 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Neuro',       'color' => '#8E44AD', 'bg' => '#F0E6F6', 'order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pédia',       'color' => '#1D8348', 'bg' => '#E6F2EC', 'order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Obstétrie',   'color' => '#E91E8C', 'bg' => '#FCE4F5', 'order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Psychiatrie', 'color' => '#5D6D7E', 'bg' => '#EAF0F6', 'order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Général',     'color' => '#4A5568', 'bg' => '#F0F2F5', 'order' => 8, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('intervention_categories');
    }
};