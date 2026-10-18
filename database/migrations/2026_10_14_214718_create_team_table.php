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
        if (Schema::hasTable('team')) {
            return;
        }

        Schema::create('team', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 64)->default('');
            $table->string('email')->default('');
            $table->text('hobbies')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team');
    }
};
