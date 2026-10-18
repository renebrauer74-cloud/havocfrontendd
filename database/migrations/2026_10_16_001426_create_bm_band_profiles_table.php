<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bandprofil: Biografie (DE/EN), Social-Media-/Streaming-Links und Bandlogo – ein Datensatz pro Band.
     */
    public function up(): void
    {
        Schema::create('bm_band_profiles', function (Blueprint $table) {
            $table->foreignId('band_id')->primary()->constrained('bm_bands')->cascadeOnDelete();
            $table->text('bio_de')->nullable();
            $table->text('bio_en')->nullable();
            $table->text('links')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_band_profiles');
    }
};
