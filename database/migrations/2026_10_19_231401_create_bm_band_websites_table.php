<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Website Creator – eine Website pro Band. Inhalte (Biografie, Fotos, Links, Shows …) kommen live aus dem
     * Band-Manager, hier stehen nur Design, Auswahl und Reihenfolge der Abschnitte.
     */
    public function up(): void
    {
        Schema::create('bm_band_websites', function (Blueprint $table) {
            $table->foreignId('band_id')->primary()->constrained('bm_bands')->cascadeOnDelete();

            $table->string('slug', 60)->unique();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            // Design
            $table->string('theme', 20);
            $table->string('font', 20);
            $table->string('hero_style', 20);
            $table->string('accent_color', 7)->nullable();
            $table->boolean('effect_grain')->default(true);
            $table->boolean('effect_animations')->default(true);

            // Inhalte
            $table->string('language', 2)->default('de');
            $table->string('tagline', 160)->nullable();
            $table->text('about_text')->nullable();
            $table->foreignId('hero_image_id')->nullable()->constrained('bm_gallery_images')->nullOnDelete();
            $table->text('gallery_image_ids')->nullable();
            $table->text('sections')->nullable();
            $table->string('spotify_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('show_agency_booking')->default(true);
            $table->text('imprint')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_band_websites');
    }
};
