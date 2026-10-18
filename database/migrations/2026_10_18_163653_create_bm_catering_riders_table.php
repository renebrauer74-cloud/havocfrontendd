<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Catering Rider (Rider Creator) – ein Datensatz pro Band. Reisegruppe, Mahlzeiten, Getränke
     * und eigene Abschnitte liegen als JSON.
     */
    public function up(): void
    {
        Schema::create('bm_catering_riders', function (Blueprint $table) {
            $table->foreignId('band_id')->primary()->constrained('bm_bands')->cascadeOnDelete();

            $table->string('contact_name', 150)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email', 190)->nullable();

            $table->text('people')->nullable();
            $table->text('diet_notes')->nullable();
            $table->text('meals')->nullable();

            $table->string('buyout', 20)->default('catering');
            $table->decimal('buyout_amount', 6, 2)->nullable();
            $table->text('buyout_notes')->nullable();

            $table->text('drinks')->nullable();
            $table->text('drink_notes')->nullable();

            $table->unsignedTinyInteger('dressing_rooms')->nullable();
            $table->unsignedSmallInteger('stage_towels')->nullable();
            $table->unsignedSmallInteger('shower_towels')->nullable();
            $table->boolean('needs_shower')->default(false);
            $table->text('dressing_room_notes')->nullable();

            $table->text('custom_sections')->nullable();
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
        Schema::dropIfExists('bm_catering_riders');
    }
};
