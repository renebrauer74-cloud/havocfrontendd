<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stage Plot (Rider Creator) – ein Datensatz pro Band. Die Elemente liegen als JSON,
     * das fertige Bild (PNG) in der Media Library.
     */
    public function up(): void
    {
        Schema::create('bm_stage_plots', function (Blueprint $table) {
            $table->foreignId('band_id')->primary()->constrained('bm_bands')->cascadeOnDelete();

            $table->decimal('stage_width', 4, 1)->default(8);
            $table->decimal('stage_depth', 4, 1)->default(6);
            $table->text('items')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_stage_plots');
    }
};
