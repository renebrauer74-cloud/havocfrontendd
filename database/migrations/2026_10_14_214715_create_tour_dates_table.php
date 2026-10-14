<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bestehende Tabellen aus dem alten database/schema.sql bleiben unangetastet.
     */
    public function up(): void
    {
        if (Schema::hasTable('tour_dates')) {
            return;
        }

        Schema::create('tour_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->string('date', 64)->default('');
            $table->string('city')->default('');
            $table->string('venue')->default('');
            $table->string('link', 1024)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_dates');
    }
};
