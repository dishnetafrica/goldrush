<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per document ever issued to an investor.
 *
 * A document is immutable once issued: the file is written, its SHA-256 is
 * stored, and asking for it again returns the same bytes rather than rendering
 * it afresh. That is what lets an investor and the company hold the same piece
 * of paper and agree about it a year later.
 *
 * Files live on the private disk and are served only through an authorised
 * route. Nothing here is reachable under /storage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->string('type', 20)->comment('statement|receipt');
            $table->string('document_number', 60)->unique();
            $table->string('title', 191);

            // What the document is about. A receipt is about one ledger movement,
            // identified by the reference every leg of it shares.
            $table->string('event_reference', 60)->nullable();
            $table->string('trx_id', 100)->nullable();
            $table->foreignId('gold_lot_id')->nullable()->constrained('gold_lots')->nullOnDelete();

            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            $table->string('file_path', 255);
            $table->string('file_hash', 64)->comment('SHA-256 of the bytes as issued');
            $table->unsignedBigInteger('file_bytes');
            $table->string('currency_code', 10)->default('USD');

            // The position the document asserts, kept so a later reconciliation can
            // check the paper against the ledger without re-rendering it.
            $table->decimal('closing_available', 24, 8)->nullable();
            $table->decimal('closing_profit', 24, 8)->nullable();
            $table->decimal('closing_committed', 24, 8)->nullable();

            $table->json('meta')->nullable();
            $table->dateTime('generated_at');
            $table->string('generated_by', 40)->default('system');
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedBigInteger('supersedes_document_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('event_reference');

            // One receipt per movement per investor. Re-requesting returns the
            // stored file instead of issuing a second number for the same event.
            $table->unique(['user_id', 'type', 'event_reference'], 'one_document_per_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_documents');
    }
};
