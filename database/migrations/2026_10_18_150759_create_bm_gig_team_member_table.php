<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reisegruppe eines Gigs: welche Bandmitglieder und Crew mitreisen.
     */
    public function up(): void
    {
        Schema::create('bm_gig_team_member', function (Blueprint $table) {
            $table->foreignId('gig_id')->constrained('bm_gigs')->cascadeOnDelete();
            $table->foreignId('team_member_id')->constrained('bm_team_members')->cascadeOnDelete();
            $table->primary(['gig_id', 'team_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_gig_team_member');
    }
};
