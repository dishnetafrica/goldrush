<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ties each gold trading record to the journal that accounts for it.
 *
 * The operational tables already describe what happened to the gold. These
 * columns say where that showed up in the company's books, so a lot can find
 * its journals and a journal can find its lot. A record with no journal id has
 * simply not been posted yet, which is a state worth being able to see.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_lots', function (Blueprint $table) {
            $table->foreignId('purchase_journal_id')->nullable()->after('recorded_by')
                ->constrained('journals')->nullOnDelete();
            $table->foreignId('paid_from_cash_account_id')->nullable()->after('purchase_journal_id')
                ->constrained('cash_accounts')->nullOnDelete();
        });

        Schema::table('gold_processings', function (Blueprint $table) {
            $table->foreignId('journal_id')->nullable()->after('recorded_by')
                ->constrained('journals')->nullOnDelete();
            // Directly attributable processing costs belong in the cost of the gold,
            // not in the period's expenses (decision D4).
            $table->boolean('cost_capitalised')->default(true)->after('cost_usd');
            $table->foreignId('paid_from_cash_account_id')->nullable()->after('journal_id')
                ->constrained('cash_accounts')->nullOnDelete();
        });

        Schema::table('gold_sales', function (Blueprint $table) {
            $table->foreignId('journal_id')->nullable()->after('recorded_by')
                ->constrained('journals')->nullOnDelete();
            $table->foreignId('proceeds_to_cash_account_id')->nullable()->after('journal_id')
                ->constrained('cash_accounts')->nullOnDelete();
        });

        Schema::table('gold_trading_expenses', function (Blueprint $table) {
            $table->foreignId('journal_id')->nullable()->after('recorded_by')
                ->constrained('journals')->nullOnDelete();
            $table->foreignId('paid_from_cash_account_id')->nullable()->after('journal_id')
                ->constrained('cash_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gold_trading_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_from_cash_account_id');
            $table->dropConstrainedForeignId('journal_id');
        });

        Schema::table('gold_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proceeds_to_cash_account_id');
            $table->dropConstrainedForeignId('journal_id');
        });

        Schema::table('gold_processings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_from_cash_account_id');
            $table->dropColumn('cost_capitalised');
            $table->dropConstrainedForeignId('journal_id');
        });

        Schema::table('gold_lots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_from_cash_account_id');
            $table->dropConstrainedForeignId('purchase_journal_id');
        });
    }
};
