<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The investor ledger: an append-only record of every movement of an investor's
 * money, split across the three buckets they are told about.
 *
 *   available  — USD they can spend or withdraw right now
 *   profit     — trading profit credited to them, also spendable
 *   committed  — their capital currently inside an open gold deal
 *
 * available + profit + committed = total investor position.
 *
 * A movement is written as one or more legs sharing a group_uuid. Internal
 * movements (money going between an investor's own buckets) must have legs that
 * sum to zero, so an internal movement can never change the total position.
 * External movements are money genuinely entering or leaving the relationship.
 *
 * Every leg carries the running balance of all three buckets immediately after
 * it, which is what makes a statement a read rather than a recomputation, and
 * what makes the reconciliation checks cheap.
 *
 * Nothing in here is ever updated or deleted. Mistakes are corrected by posting
 * a further movement that reverses or adjusts the first one, so the error and
 * its correction both stay visible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('seq')->comment('Per investor, strictly increasing');

            $table->dateTime('occurred_at');
            $table->date('entry_date');

            $table->string('event_type', 40);
            $table->string('bucket', 20)->comment('available|profit|committed');
            $table->decimal('amount_usd', 20, 8)->comment('Signed: negative takes money out of the bucket');
            $table->string('flow', 20)->comment('external_in|external_out|internal|marker');
            $table->uuid('group_uuid')->comment('All legs of one movement share this');

            $table->decimal('balance_available', 24, 8);
            $table->decimal('balance_profit', 24, 8);
            $table->decimal('balance_committed', 24, 8);

            $table->string('reference', 60)->comment('Human facing, e.g. CAP-20260916-000001');

            // The original identifiers are preserved exactly as the platform wrote
            // them. Nothing here renames an existing transaction.
            $table->string('trx_id', 100)->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();

            $table->foreignId('gold_lot_id')->nullable()->constrained('gold_lots')->nullOnDelete();
            $table->unsignedBigInteger('allocation_id')->nullable();
            $table->unsignedBigInteger('reverses_entry_id')->nullable();

            $table->string('description', 191);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'seq']);
            $table->unique(['reference', 'bucket'], 'ledger_reference_per_bucket');
            $table->index(['user_id', 'occurred_at']);
            $table->index(['user_id', 'event_type']);
            $table->index('group_uuid');
            $table->index('transaction_id');
        });

        // Gapless, race free reference numbering. The counter row is locked for the
        // duration of the posting transaction, so two concurrent movements cannot
        // be handed the same number.
        Schema::create('ledger_reference_sequences', function (Blueprint $table) {
            $table->string('prefix', 20);
            $table->date('day');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();

            $table->primary(['prefix', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_reference_sequences');
        Schema::dropIfExists('investor_ledger_entries');
    }
};
