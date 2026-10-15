<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Auftritte pro Band inkl. aller Day-Sheet-Felder.
     */
    public function up(): void
    {
        Schema::create('bm_gigs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->date('date');
            $table->string('title', 190);
            $table->string('venue', 190)->nullable();
            $table->string('city', 120)->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->string('status', 20)->default('request');
            $table->string('booked_by', 10)->default('agency');   // 'agency' | 'band'
            $table->text('notes')->nullable();

            // Day Sheet – Location
            $table->string('venue_street', 150)->nullable();
            $table->string('venue_zip', 10)->nullable();
            $table->string('venue_country', 60)->nullable();
            $table->text('parking_info')->nullable();

            // Day Sheet – Zeitplan
            $table->time('time_get_in')->nullable();
            $table->time('time_soundcheck')->nullable();
            $table->time('time_dinner')->nullable();
            $table->time('time_doors')->nullable();
            $table->time('time_show')->nullable();
            $table->unsignedSmallInteger('set_length')->nullable();
            $table->time('time_curfew')->nullable();
            $table->text('schedule_notes')->nullable();

            // Day Sheet – Unterkunft
            $table->string('hotel_name', 150)->nullable();
            $table->string('hotel_address', 255)->nullable();
            $table->string('hotel_phone', 50)->nullable();
            $table->time('hotel_checkin')->nullable();
            $table->time('hotel_checkout')->nullable();
            $table->text('hotel_notes')->nullable();

            // Day Sheet – Verpflegung
            $table->string('catering', 20)->nullable();
            $table->decimal('buyout_amount', 6, 2)->nullable();
            $table->text('catering_notes')->nullable();

            // Day Sheet – Ansprechpartner & Sonstiges
            $table->string('contact_venue_name', 100)->nullable();
            $table->string('contact_venue_phone', 50)->nullable();
            $table->string('contact_tech_name', 100)->nullable();
            $table->string('contact_tech_phone', 50)->nullable();
            $table->string('guestlist', 150)->nullable();
            $table->string('wifi', 150)->nullable();
            $table->text('backline_info')->nullable();
            $table->text('merch_info')->nullable();
            $table->text('info_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['band_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_gigs');
    }
};
