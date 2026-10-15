<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nachrichten zwischen Agentur und Bands: Unterhaltungen pro Band, Gelesen-Status
     * und "für mich ausgeblendet" je Benutzer.
     */
    public function up(): void
    {
        Schema::create('bm_message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bm_bands')->cascadeOnDelete();
            $table->string('subject', 190);
            $table->foreignId('created_by')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->dateTime('last_message_at')->useCurrent();
            $table->timestamps();

            $table->index(['band_id', 'last_message_at']);
        });

        Schema::create('bm_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('bm_message_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('bm_users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('bm_message_reads', function (Blueprint $table) {
            $table->foreignId('thread_id')->constrained('bm_message_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('bm_users')->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_id')->default(0);
            $table->primary(['thread_id', 'user_id']);
        });

        Schema::create('bm_message_hidden', function (Blueprint $table) {
            $table->foreignId('thread_id')->constrained('bm_message_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('bm_users')->cascadeOnDelete();
            $table->unsignedBigInteger('up_to_id')->default(0);
            $table->primary(['thread_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bm_message_hidden');
        Schema::dropIfExists('bm_message_reads');
        Schema::dropIfExists('bm_messages');
        Schema::dropIfExists('bm_message_threads');
    }
};
