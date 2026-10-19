<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Website Creator: eigene Schrift für den Fließtext, eigene Überschriften und Einleitungen je Abschnitt,
     * Logo ausblendbar und einzelne Bandmitglieder, Links und Gigs von der Website ausnehmen.
     */
    public function up(): void
    {
        Schema::table('bm_band_websites', function (Blueprint $table) {
            $table->string('body_font', 20)->nullable()->after('font');
            $table->boolean('show_logo')->default(true)->after('language');
            $table->text('section_texts')->nullable()->after('sections');
            $table->text('hidden_member_ids')->nullable()->after('section_texts');
            $table->text('hidden_link_platforms')->nullable()->after('hidden_member_ids');
            $table->text('hidden_gig_ids')->nullable()->after('hidden_link_platforms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bm_band_websites', function (Blueprint $table) {
            $table->dropColumn(['body_font', 'show_logo', 'section_texts', 'hidden_member_ids', 'hidden_link_platforms', 'hidden_gig_ids']);
        });
    }
};
