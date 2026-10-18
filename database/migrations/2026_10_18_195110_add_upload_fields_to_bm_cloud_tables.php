<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Upload-Links: Externe (z. B. Fotografen nach einem Gig) laden in einen freigegebenen Ordner hoch.
     * Am Link: Hinweistext und ob alle Dateitypen erlaubt sind (sonst nur Fotos & Videos).
     * An der Datei: über welchen Link und von wem (freiwillige Namensangabe) sie kam.
     */
    public function up(): void
    {
        Schema::table('bm_cloud_links', function (Blueprint $table) {
            $table->string('message', 500)->nullable()->after('type');
            $table->boolean('allow_all_types')->default(false)->after('message');
        });

        Schema::table('bm_cloud_files', function (Blueprint $table) {
            $table->foreignId('link_id')->nullable()->after('uploaded_by')->constrained('bm_cloud_links')->nullOnDelete();
            $table->string('uploader_name', 150)->nullable()->after('link_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bm_cloud_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('link_id');
            $table->dropColumn('uploader_name');
        });

        Schema::table('bm_cloud_links', function (Blueprint $table) {
            $table->dropColumn(['message', 'allow_all_types']);
        });
    }
};
