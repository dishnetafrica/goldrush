<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The company's general ledger.
 *
 * This is the company side of the business: what it owns, what it owes, what it
 * earned and what it spent. It sits alongside the investor ledger rather than
 * replacing it. The two meet at two control accounts — investor capital payable
 * and investor profit payable — whose balances must equal the investor ledger's
 * buckets exactly. Nothing else connects them.
 *
 * Posted journals are immutable. A mistake is corrected by posting a reversing
 * journal, never by editing what was recorded, so the error and its correction
 * both remain visible. The same discipline the investor ledger already follows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('type', 20)->comment('asset|liability|equity|revenue|cogs|expense');
            $table->string('normal_balance', 6)->comment('debit|credit');
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();

            // A control account's balance must equal a figure held elsewhere. The
            // investor capital and profit accounts are reconciled against the
            // investor ledger; a mismatch means the two systems disagree about
            // what the company owes.
            $table->string('control_of', 40)->nullable()
                ->comment('investor_capital|investor_profit|inventory|cash — what this account controls');

            $table->string('currency_code', 10)->default('USD');
            $table->boolean('active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['type', 'active']);
        });

        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('e.g. 2026-09');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('open')->comment('open|in_review|closed|locked');

            $table->foreignId('closed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('locked_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();

            $table->timestamps();
            $table->index(['starts_on', 'ends_on']);
            $table->index('status');
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 60)->unique();
            $table->date('journal_date');
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->restrictOnDelete();

            $table->string('memo', 191);

            // Every journal points back at the business event that caused it, and
            // every such event can find its journal. Nothing is posted from
            // nowhere.
            $table->string('source_type', 60)->nullable()->comment('gold_lot|gold_sale|expense|distribution|manual|opening');
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('status', 20)->default('posted')->comment('posted|reversed');
            $table->foreignId('posted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('posted_at');

            $table->foreignId('reverses_journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->unsignedBigInteger('reversed_by_journal_id')->nullable();
            $table->text('reversal_reason')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['accounting_period_id', 'journal_date']);
            $table->index(['source_type', 'source_id']);
            $table->index('status');
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->unsignedInteger('line_no');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();

            // Exactly one of these is non-zero on any line. Keeping them as two
            // columns rather than one signed amount is what makes a trial balance
            // a sum rather than an interpretation.
            $table->decimal('debit', 24, 8)->default(0);
            $table->decimal('credit', 24, 8)->default(0);

            $table->string('memo', 191)->nullable();

            // Analysis dimensions. A line may say which deal or which investor it
            // concerns without that ever being a second ledger.
            $table->foreignId('gold_lot_id')->nullable()->constrained('gold_lots')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['journal_id', 'line_no']);
            $table->index(['account_id', 'journal_id']);
            $table->index('gold_lot_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('accounts');
    }
};
