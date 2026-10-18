<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Allergie einer Person (App\Enums\BandManager\AllergyEnum), bei „Sonstiges“ mit eigenem Text.
     */
    public function up(): void
    {
        Schema::table('bm_team_members', function (Blueprint $table) {
            $table->string('allergy', 20)->nullable()->after('diet');
            $table->string('allergy_other', 150)->nullable()->after('allergy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bm_team_members', function (Blueprint $table) {
            $table->dropColumn(['allergy', 'allergy_other']);
        });
    }
};
