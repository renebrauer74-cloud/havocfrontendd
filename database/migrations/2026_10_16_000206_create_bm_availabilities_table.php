<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ampel-Kalender: ein Status pro Band und Tag.
     */
    public function up(): void
    {
        Schema::create('bm_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20);
            $table->string('note', 500)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['band_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_availabilities');
    }
};
