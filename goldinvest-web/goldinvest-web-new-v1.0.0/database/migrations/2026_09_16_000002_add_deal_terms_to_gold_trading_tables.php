<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deal terms are negotiated per lot, not set once for the platform: the split
 * depends on market demand and on the source of the gold. These columns record
 * the terms that were agreed for each deal, and for each investor inside a deal
 * when their terms differ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_lots', function (Blueprint $table) {
            $table->decimal('investor_share_percent', 9, 4)->nullable()->after('status')
                ->comment('Agreed share of this deal\'s net profit owed to investors');
            $table->string('expense_policy', 40)->default('deal_before_split')->after('investor_share_percent')
                ->comment('deal_before_split = expenses reduce net profit before the split; company_share = company absorbs them');
            $table->string('terms_note', 191)->nullable()->after('expense_policy')
                ->comment('Why these terms were agreed, e.g. demand, source, risk');
        });

        Schema::table('gold_capital_allocations', function (Blueprint $table) {
            $table->decimal('share_percent', 9, 4)->nullable()->after('amount_usd')
                ->comment('This investor\'s agreed profit share for this deal; falls back to the lot terms when null');
        });
    }

    public function down(): void
    {
        Schema::table('gold_capital_allocations', function (Blueprint $table) {
            $table->dropColumn('share_percent');
        });

        Schema::table('gold_lots', function (Blueprint $table) {
            $table->dropColumn(['investor_share_percent', 'expense_policy', 'terms_note']);
        });
    }
};
