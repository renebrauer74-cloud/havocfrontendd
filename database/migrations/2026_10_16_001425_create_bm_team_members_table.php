<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bandmitglieder (type "member") und Crew (type "crew") mit Steckbrief und Stammdaten.
     */
    public function up(): void
    {
        Schema::create('bm_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->string('type', 10)->default('member');
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('stage_name', 100)->nullable();
            $table->string('position', 150)->nullable();          // Instrument bzw. Zusatz
            $table->string('crew_role', 30)->nullable();          // nur bei Crew
            $table->string('crew_role_other', 100)->nullable();   // Aufgabe bei "Sonstiges"
            $table->text('bio')->nullable();
            $table->string('photo', 64)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('street', 190)->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('shirt_size', 10)->nullable();
            $table->string('diet', 255)->nullable();
            $table->string('driver_license', 50)->nullable();
            $table->string('emergency_contact', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['band_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_team_members');
    }
};
