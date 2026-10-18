<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cloud-Speicher der Bands: Ordner (beliebig tief) und Dateien (Media Library, Disk "band-manager").
     * Gelöschte Einträge landen per Soft Delete im Papierkorb. Dateien halten ihren Ordner fest
     * (restrict), damit Ordner nur über die Actions gelöscht werden – die räumen vorher die Dateien im Bucket ab.
     */
    public function up(): void
    {
        Schema::create('bm_cloud_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('bm_cloud_folders')->cascadeOnDelete();
            $table->string('name', 150);
            $table->boolean('is_preset')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['band_id', 'parent_id']);
        });

        Schema::create('bm_cloud_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('bm_cloud_folders')->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['band_id', 'folder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_cloud_files');
        Schema::dropIfExists('bm_cloud_folders');
    }
};
