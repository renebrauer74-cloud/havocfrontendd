<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bandfotos (Original + Vorschau), genau ein Hauptbild pro Band.
     */
    public function up(): void
    {
        Schema::create('bm_gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->string('category', 20)->default('band');
            $table->string('caption', 190)->nullable();
            $table->string('credit', 150)->nullable();
            $table->boolean('is_main')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('file', 64);
            $table->string('preview', 64)->nullable();
            $table->string('original_name', 255);
            $table->string('mime', 30);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['band_id', 'is_main', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_gallery_images');
    }
};
