<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bereiche, die eine Band einem Zugang (Bandmitglied, Team, Fotograf) freigibt: {"gallery": "edit", "gigs": "view"}.
     */
    public function up(): void
    {
        Schema::table('bm_users', function (Blueprint $table) {
            $table->json('area_access')->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bm_users', function (Blueprint $table) {
            $table->dropColumn('area_access');
        });
    }
};
