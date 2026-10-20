<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * „Passwort vergessen“ im Band-Manager – getrennt von den Tokens der Website-Admins,
     * damit sich gleiche E-Mail-Adressen in bm_users und users nicht in die Quere kommen.
     */
    public function up(): void
    {
        Schema::create('bm_password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 190)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_password_reset_tokens');
    }
};
