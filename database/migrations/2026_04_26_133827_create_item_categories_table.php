<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Catégories par défaut
        DB::table('item_categories')->insert([
            ['name' => 'Airway',         'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Breathing',      'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Circulation',    'order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Médicaments',    'order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Immobilisation', 'order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Divers',         'order' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};