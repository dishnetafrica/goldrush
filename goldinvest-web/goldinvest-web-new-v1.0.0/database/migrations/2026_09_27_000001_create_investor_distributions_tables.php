<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A period's investor distribution: the moment a calculated allocation became
 * a liability the company owes and a credit the investors hold.
 *
 * This is not a second investor balance. The credit itself lives in the
 * investor ledger and the wallet, through the same door every other credit
 * uses; the liability lives in the general ledger on 2010. What these tables
 * hold is the act: which period, under which allocation, to whom, by whom,
 * when, and the exact figures as they stood - so that a distribution can be
 * checked long afterwards against records that have not moved since.
 *
 * One distribution per period. The uniqueness is on (period, active_seq): a
 * posted distribution holds active_seq 0, and a reversed one takes its own id,
 * which is what lets a period be distributed again after a reversal without
 * the original ever being overwritten. A nullable column would not do; MySQL
 * treats NULLs in a unique index as distinct.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_distributions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique()->comment('DIST-<period>-NNNNNN');
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->restrictOnDelete();

            // The appropriation in the company's books: Dr 7000 / Cr 2010.
            $table->foreignId('journal_id')->constrained('journals')->restrictOnDelete();

            $table->decimal('company_result_usd', 20, 8)->comment('The period trading result the allocation was made from');
            $table->decimal('gross_pool_usd', 20, 8);
            $table->decimal('reserve_usd', 20, 8)->default(0);
            $table->decimal('pool_usd', 20, 8)->comment('Exactly the sum of the lines');
            $table->unsignedInteger('investors_count');

            $table->string('status', 20)->default('posted')->comment('posted|reversed');
            $table->unsignedBigInteger('active_seq')->default(0)->comment('0 while posted; own id once reversed');

            $table->json('snapshot')->comment('The allocation exactly as it stood when distributed');

            $table->foreignId('distributed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('distributed_at');

            $table->foreignId('reversal_journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();

            $table->timestamps();

            $table->unique(['accounting_period_id', 'active_seq'], 'investor_distributions_one_per_period');
        });

        Schema::create('investor_distribution_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_distribution_id')->constrained('investor_distributions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->decimal('amount_usd', 20, 8);

            // Where the credit went: the transaction the application sees, the
            // ledger entry that records it, and the reference on that entry.
            $table->string('trx_id', 100);
            $table->unsignedBigInteger('transaction_id');
            $table->foreignId('ledger_entry_id')->constrained('investor_ledger_entries')->restrictOnDelete();
            $table->string('ledger_reference', 60);

            $table->json('snapshot')->comment('This investor\'s share, deal by deal');

            $table->unsignedBigInteger('reversal_transaction_id')->nullable();
            $table->foreignId('reversal_ledger_entry_id')->nullable()->constrained('investor_ledger_entries')->nullOnDelete();

            $table->timestamps();

            // The same investor cannot be credited twice by the same distribution.
            $table->unique(['investor_distribution_id', 'user_id'], 'investor_distribution_lines_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_distribution_lines');
        Schema::dropIfExists('investor_distributions');
    }
};
