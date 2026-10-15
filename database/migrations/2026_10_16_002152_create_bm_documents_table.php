<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Verträge (Agentur, optional mit Gig verknüpft) und Rider (Bands) als PDF.
     * Wird ein Gig gelöscht, bleibt der Vertrag bei der Band – nur die Verknüpfung fällt weg.
     */
    public function up(): void
    {
        Schema::create('bm_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->foreignId('gig_id')->nullable()->constrained('bm_gigs')->nullOnDelete();
            $table->string('type', 20);             // 'contract' | 'rider'
            $table->string('category', 30)->nullable();   // nur bei Ridern
            $table->string('title', 190);
            $table->string('original_name', 255);
            $table->string('stored_name', 64);
            $table->unsignedInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['band_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_documents');
    }
};
