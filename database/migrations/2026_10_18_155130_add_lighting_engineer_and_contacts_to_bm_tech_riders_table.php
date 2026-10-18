<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Eigener Lichttechniker und weitere technische Ansprechpartner (Ton, Licht, Backline …) als JSON-Liste.
     */
    public function up(): void
    {
        Schema::table('bm_tech_riders', function (Blueprint $table) {
            $table->text('contacts')->nullable()->after('contact_email');
            $table->boolean('brings_lighting_engineer')->default(false)->after('brings_monitor_engineer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bm_tech_riders', function (Blueprint $table) {
            $table->dropColumn(['contacts', 'brings_lighting_engineer']);
        });
    }
};
