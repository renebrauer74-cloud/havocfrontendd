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
        if (Schema::hasTable('agency_shows')) {
            return;
        }

        Schema::create('agency_shows', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('date', 64)->default('');
            $table->string('city')->default('');
            $table->string('venue')->default('');
            $table->string('ticket_url', 1024)->default('');
            $table->text('info_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_shows');
    }
};
