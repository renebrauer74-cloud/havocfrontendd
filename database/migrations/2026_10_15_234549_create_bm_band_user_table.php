<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Welche Logins Zugriff auf welche Band haben.
     */
    public function up(): void
    {
        Schema::create('bm_band_user', function (Blueprint $table) {
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('bm_users')->cascadeOnDelete();
            $table->primary(['band_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_band_user');
    }
};
