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
        if (Schema::hasTable('artists')) {
            return;
        }

        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('genre')->default('');
            $table->text('bio')->nullable();
            $table->text('bio_en')->nullable();
            $table->string('spotify_url', 1024)->default('');
            $table->string('youtube_url', 1024)->default('');
            $table->string('website_url', 1024)->default('');
            $table->string('instagram_url', 1024)->default('');
            $table->string('tiktok_url', 1024)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
