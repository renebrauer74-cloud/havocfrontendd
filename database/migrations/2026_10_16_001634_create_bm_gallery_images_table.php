<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bandfotos (Original + Vorschau als Media), genau ein Hauptbild pro Band.
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
