<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company cash boxes and bank accounts, and the bank reconciliation that keeps
 * them honest.
 *
 * There is deliberately no "cash movements" table. A movement of money is a
 * journal line against that account's own GL account — that is what a movement
 * is. Recording it twice would create two sets of books that could disagree,
 * which is the failure this whole phase exists to prevent.
 *
 * Each cash or bank account therefore owns exactly one GL account, and its
 * balance is that account's balance. Nothing to reconcile between them because
 * there is only one number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('type', 10)->comment('cash|bank');

            // Exactly one GL account per cash account, and no sharing: the unique
            // key is what makes "the account's balance" a single unambiguous figure.
            $table->foreignId('gl_account_id')->unique()->constrained('accounts')->restrictOnDelete();

            $table->string('currency_code', 10)->default('USD');
            $table->string('bank_name', 150)->nullable();
            $table->string('account_ref', 100)->nullable()->comment('Account number or identifier at the bank');

            // Money the company does not have cannot leave it. An overdraft is a
            // real arrangement, so it can be permitted per account, explicitly.
            $table->boolean('allow_negative')->default(false);
            $table->decimal('overdraft_limit', 24, 8)->default(0);

            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'active']);
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->restrictOnDelete();

            $table->string('statement_ref', 100)->nullable()->comment('Which statement this line came from');
            $table->date('value_date');
            $table->string('description', 191);

            // Signed: positive is money arriving, negative is money leaving. The
            // bank's point of view, not ours.
            $table->decimal('amount', 24, 8);

            $table->string('external_ref', 100)->nullable()->comment("The bank's own reference for the line");
            $table->string('fingerprint', 64)->comment('Hash of account, date, amount, description and ref');

            $table->string('status', 20)->default('unmatched')->comment('unmatched|matched|ignored');

            // A statement line may be matched to exactly one journal line, and a
            // journal line may answer exactly one statement line.
            $table->foreignId('matched_journal_line_id')->nullable()->unique()
                ->constrained('journal_lines')->nullOnDelete();

            $table->foreignId('matched_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('matched_at')->nullable();
            $table->text('ignore_reason')->nullable()->comment('Why a line was set aside; never blank when ignored');

            $table->foreignId('imported_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // Importing the same statement twice must not create the line twice.
            $table->unique(['cash_account_id', 'fingerprint'], 'bank_line_once_per_account');
            $table->index(['cash_account_id', 'status']);
            $table->index('value_date');
        });

        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 60)->unique();
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->restrictOnDelete();

            $table->date('as_at');
            $table->decimal('statement_closing_balance', 24, 8);
            $table->decimal('ledger_balance', 24, 8);
            $table->decimal('difference', 24, 8);

            $table->unsignedInteger('lines_total')->default(0);
            $table->unsignedInteger('lines_matched')->default(0);
            $table->unsignedInteger('lines_unmatched')->default(0);
            $table->unsignedInteger('lines_ignored')->default(0);

            $table->string('status', 20)->default('draft')->comment('draft|completed');
            $table->foreignId('performed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('snapshot')->nullable()->comment('The figures as they stood when completed');
            $table->timestamps();

            $table->index(['cash_account_id', 'as_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('cash_accounts');
    }
};
