<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a period close stood behind.
 *
 * A period already records that it was closed and by whom. What it did not
 * record is what was true when it happened: the trading result, the trial
 * balance, which deals were counted. Without that, a closed period is an
 * assertion nobody can check afterwards, and "the books said so at the time" is
 * exactly the claim a close exists to make.
 *
 * The snapshot is the company's own figures only. What share of a result
 * becomes an investor's is a separate decision taken after the close, and
 * writing a figure for it here would be answering a question this phase has
 * deliberately not asked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->string('close_reference', 60)->nullable()->unique()->after('closed_at')
                ->comment('PCL-YYYYMMDD-NNNNNN, allocated when the period is closed');
            $table->text('close_reason')->nullable()->after('close_reference');

            // The figures as they stood at the moment of closing, so the close can
            // be checked against the ledger long afterwards.
            $table->json('snapshot')->nullable()->after('close_reason');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_periods', function (Blueprint $table) {
            $table->dropUnique(['close_reference']);
            $table->dropColumn(['close_reference', 'close_reason', 'snapshot']);
        });
    }
};
