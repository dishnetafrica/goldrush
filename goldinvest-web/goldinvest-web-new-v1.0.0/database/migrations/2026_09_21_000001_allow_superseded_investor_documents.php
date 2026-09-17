<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a document be superseded without colliding with its replacement.
 *
 * The original key was unique on (user_id, type, event_reference), which meant
 * one document per event ever. That is right for the live document and wrong
 * for its history: when a document has to be reissued, the superseded one is
 * kept — and then the replacement could not be written at all.
 *
 * A nullable column would not do the job, because MySQL treats NULLs in a
 * unique index as distinct and would happily allow two live documents for the
 * same event. So supersede_seq is zero while a document is current and takes
 * the row's own id once it is superseded: exactly one live document per event,
 * and any number of superseded ones behind it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investor_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('supersede_seq')->default(0)->after('supersedes_document_id')
                ->comment('0 while current; the row id once superseded, so only one document per event is live');
        });

        // Anything already revoked is history, not the live document.
        DB::table('investor_documents')->whereNotNull('revoked_at')->update([
            'supersede_seq' => DB::raw('id'),
        ]);

        Schema::table('investor_documents', function (Blueprint $table) {
            $table->dropUnique('one_document_per_event');
            $table->unique(['user_id', 'type', 'event_reference', 'supersede_seq'], 'one_live_document_per_event');
        });
    }

    public function down(): void
    {
        Schema::table('investor_documents', function (Blueprint $table) {
            $table->dropUnique('one_live_document_per_event');
            $table->unique(['user_id', 'type', 'event_reference'], 'one_document_per_event');
            $table->dropColumn('supersede_seq');
        });
    }
};
