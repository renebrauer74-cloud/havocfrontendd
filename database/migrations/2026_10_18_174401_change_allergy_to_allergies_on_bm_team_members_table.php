<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mehrere Allergien je Person (Liste aus App\Enums\BandManager\AllergyEnum) statt einer – die bisherige Angabe wird übernommen.
     */
    public function up(): void
    {
        Schema::table('bm_team_members', function (Blueprint $table) {
            $table->json('allergies')->nullable()->after('diet');
        });

        DB::table('bm_team_members')->whereNotNull('allergy')->eachById(function (object $member): void {
            DB::table('bm_team_members')->where('id', $member->id)->update(['allergies' => json_encode([$member->allergy])]);
        });

        Schema::table('bm_team_members', function (Blueprint $table) {
            $table->dropColumn('allergy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bm_team_members', function (Blueprint $table) {
            $table->string('allergy', 20)->nullable()->after('diet');
        });

        DB::table('bm_team_members')->whereNotNull('allergies')->eachById(function (object $member): void {
            DB::table('bm_team_members')->where('id', $member->id)->update(['allergy' => json_decode($member->allergies, true)[0] ?? null]);
        });

        Schema::table('bm_team_members', function (Blueprint $table) {
            $table->dropColumn('allergies');
        });
    }
};
