<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a deal actually realized, recorded against the deal it belongs to.
 *
 * There is no new results table. `gold_lot_results` already holds one row per
 * deal; these columns say which accounting period its figures belong to and when
 * somebody stood behind them. A second table would be a second opinion about the
 * same money.
 *
 * The distinction the columns exist to keep is between a figure that is merely
 * current and a figure that is final. A deal whose gold has all been sold still
 * has an interim result while any cost is outstanding, and `realized_at` is what
 * separates the two. Nothing sets it by accident: it is set by recording the
 * result deliberately, and once set the figures beneath it stop moving.
 *
 * `realized_at` is written only by the recorder, which derives every figure from
 * posted journals, so its presence is by itself the statement that these numbers
 * came out of the general ledger. Nothing already in this table is reclassified:
 * rows that predate the ledger simply do not have it set, which is exactly what
 * is true of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_lot_results', function (Blueprint $table) {
            // When somebody stood behind these figures, and who.
            $table->dateTime('realized_at')->nullable()->after('status');
            $table->foreignId('realized_by')->nullable()->after('realized_at')
                ->constrained('admins')->nullOnDelete();

            // The period the result belongs to: the one the deal's last sale fell
            // in, not the one somebody happened to press the button in.
            $table->foreignId('accounting_period_id')->nullable()->after('realized_by')
                ->constrained('accounting_periods')->nullOnDelete();

            // Revenue as the general ledger holds it, kept beside the proceeds the
            // sales records claim. They are meant to be the same number, and
            // keeping both is what lets anybody check that they are.
            $table->decimal('revenue_usd', 20, 8)->default(0)->after('proceeds_usd');

            // Costs that went into the gold rather than into the period's
            // expenses (decision D4), carried here so a reader can see why cost
            // of sales is larger than the purchase price.
            $table->decimal('capitalised_cost_usd', 20, 8)->default(0)->after('cost_of_goods_sold_usd');

            // The figures exactly as they stood when the result was recorded.
            $table->json('snapshot')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('gold_lot_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accounting_period_id');
            $table->dropConstrainedForeignId('realized_by');
            $table->dropColumn([
                'realized_at', 'revenue_usd', 'capitalised_cost_usd', 'snapshot',
            ]);
        });
    }
};
