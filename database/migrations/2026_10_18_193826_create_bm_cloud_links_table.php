<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Freigabe-Links für Externe: ein Ordner (mit allem darunter) oder eine einzelne Datei.
     * Wird das Ziel endgültig gelöscht, verschwindet der Link mit.
     */
    public function up(): void
    {
        Schema::create('bm_cloud_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('bm_cloud_folders')->cascadeOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('bm_cloud_files')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('type', 20)->default('read');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();

            $table->index('band_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_cloud_links');
    }
};
