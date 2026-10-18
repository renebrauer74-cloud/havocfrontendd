<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Titel (Pflicht bei „Sonstiges“) und Uhrzeiten eines Kalendereintrags – ohne Startzeit gilt er als ganztägig.
     */
    public function up(): void
    {
        Schema::table('bm_availabilities', function (Blueprint $table) {
            $table->string('title', 100)->nullable()->after('status');
            $table->time('start_time')->nullable()->after('title');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bm_availabilities', function (Blueprint $table) {
            $table->dropColumn(['title', 'start_time', 'end_time']);
        });
    }
};
