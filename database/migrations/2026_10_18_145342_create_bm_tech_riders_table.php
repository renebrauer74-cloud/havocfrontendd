<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Technischer Rider (Rider Creator) – ein Datensatz pro Band. Listen (Besetzung, Kanalliste,
     * Monitoring, Backline, eigene Abschnitte) liegen als JSON, der Bühnenplan in der Media Library.
     */
    public function up(): void
    {
        Schema::create('bm_tech_riders', function (Blueprint $table) {
            $table->foreignId('band_id')->primary()->constrained('bm_bands')->cascadeOnDelete();

            $table->string('contact_name', 150)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->boolean('brings_foh_engineer')->default(false);
            $table->boolean('brings_monitor_engineer')->default(false);

            $table->unsignedSmallInteger('setup_minutes')->nullable();
            $table->unsignedSmallInteger('soundcheck_minutes')->nullable();
            $table->unsignedSmallInteger('show_minutes')->nullable();
            $table->unsignedSmallInteger('teardown_minutes')->nullable();

            $table->text('line_up')->nullable();
            $table->text('inputs')->nullable();
            $table->text('monitors')->nullable();
            $table->text('backline')->nullable();
            $table->text('custom_sections')->nullable();

            $table->text('pa_notes')->nullable();
            $table->text('monitor_notes')->nullable();
            $table->decimal('stage_width', 4, 1)->nullable();
            $table->decimal('stage_depth', 4, 1)->nullable();
            $table->text('stage_notes')->nullable();
            $table->text('lighting_notes')->nullable();
            $table->text('power_notes')->nullable();
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
        Schema::dropIfExists('bm_tech_riders');
    }
};
