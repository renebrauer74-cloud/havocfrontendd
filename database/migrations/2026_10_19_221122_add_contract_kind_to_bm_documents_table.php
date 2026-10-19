<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Art eines Vertrags (App\Enums\BandManager\ContractKindEnum) – bei Ridern leer.
     * Vorhandene Verträge: mit Gig → Gastspielvertrag, ohne Gig → Agenturvertrag.
     */
    public function up(): void
    {
        Schema::table('bm_documents', function (Blueprint $table) {
            $table->string('contract_kind', 20)->nullable()->after('type');
        });

        DB::table('bm_documents')->where('type', 'contract')->whereNotNull('gig_id')->update(['contract_kind' => 'gig']);
        DB::table('bm_documents')->where('type', 'contract')->whereNull('gig_id')->update(['contract_kind' => 'agency']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bm_documents', function (Blueprint $table) {
            $table->dropColumn('contract_kind');
        });
    }
};
