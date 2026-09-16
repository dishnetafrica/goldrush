<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per profit payment made to an investor for a deal.
 *
 * This is the audit trail between a gold lot's result and the money that appeared
 * in an investor's account. The unique key on (gold_lot_id, user_id) is what stops
 * the same deal being paid twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_profit_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gold_lot_id')->constrained('gold_lots')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('capital_usd', 20, 8)->comment('Capital this investor had in the lot');
            $table->decimal('capital_share_percent', 9, 4);
            $table->decimal('profit_share_percent', 9, 4);
            $table->decimal('amount_usd', 20, 8)->comment('Profit actually credited');
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->string('trx_id', 100)->nullable()->comment('transactions.trx_id written for this payment');
            $table->string('credited_to', 20)->default('profit_balance')->comment('profit_balance|balance');
            $table->date('distributed_at');
            $table->string('status', 30)->default('paid')->comment('paid|reversed');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['gold_lot_id', 'user_id'], 'gold_profit_once_per_investor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_profit_distributions');
    }
};
