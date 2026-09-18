<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Until now an allocation was attribution only: it recorded whose money funded a
 * lot but never touched the investor's wallet. That left money the company had
 * already spent on gold sitting in the investor's spendable balance, where Money
 * Out would happily pay it out a second time.
 *
 * These columns let an allocation commit the money instead: the investor's
 * spendable balance is debited when capital goes into a deal and credited back
 * when the deal closes, with a transactions row on both sides so the investor can
 * see it. Attribution-only allocations are still possible (locked_balance = 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_capital_allocations', function (Blueprint $table) {
            $table->boolean('locked_balance')->default(false)->after('share_percent')
                ->comment('1 = the investor wallet was debited, so this money cannot be withdrawn while the deal is open');
            $table->string('locked_from', 20)->nullable()->after('locked_balance')
                ->comment('balance|profit_balance|mixed — which wallet the capital was taken from');
            $table->decimal('locked_from_balance_usd', 20, 8)->default(0)->after('locked_from');
            $table->decimal('locked_from_profit_usd', 20, 8)->default(0)->after('locked_from_balance_usd');
            $table->string('lock_trx_id', 100)->nullable()->after('locked_from_profit_usd')
                ->comment('transactions.trx_id of the debit');
            $table->string('release_trx_id', 100)->nullable()->after('lock_trx_id')
                ->comment('transactions.trx_id of the return');
            $table->date('released_at')->nullable()->after('release_trx_id');
        });
    }

    public function down(): void
    {
        Schema::table('gold_capital_allocations', function (Blueprint $table) {
            $table->dropColumn([
                'locked_balance', 'locked_from', 'locked_from_balance_usd',
                'locked_from_profit_usd', 'lock_trx_id', 'release_trx_id', 'released_at',
            ]);
        });
    }
};
