<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The company expense lifecycle, added to the table that already holds costs.
 *
 * There is deliberately no new expenses table. A second one would be a second
 * set of books about the same money, and the two would eventually disagree —
 * the failure the whole of Phase 3 exists to prevent. What an expense needs is
 * not another home but a life story: who claimed it, who checked it, who
 * approved it, when it reached the general ledger and when it was actually
 * paid. Those are the columns below.
 *
 * The money itself is still only ever in one place. An expense is a claim until
 * it is posted; once posted, the journal is the expense, exactly as a journal
 * line on a cash account is the payment.
 *
 * Rows that existed before this workflow did are marked "recorded". They were
 * entered directly and were never submitted or approved by anybody, and saying
 * so is more honest than back-dating them into a state they never passed
 * through.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_trading_expenses', function (Blueprint $table) {
            // Identity
            $table->string('reference', 40)->nullable()->after('id')
                ->comment('EXP-YYYYMMDD-NNNNNN, allocated when the expense is first raised');
            $table->string('status', 20)->default('draft')->after('reference')
                ->comment('draft|submitted|approved|rejected|posted|reversed|recorded');

            // Who was paid, and what they called it. A supplier reference is how
            // the same invoice gets claimed twice, so it is what duplicates are
            // caught on.
            $table->string('payee_name', 191)->nullable()->after('description');
            $table->string('external_ref', 100)->nullable()->after('payee_name')
                ->comment("The supplier's own invoice or receipt number");

            // Decision D4. A cost that prepares gold for sale is part of what the
            // gold cost; it goes into inventory and must never be recognised as an
            // expense as well.
            $table->boolean('capitalised')->default(false)->after('amount_usd');
            $table->string('capitalised_into', 10)->nullable()->after('capitalised')
                ->comment('Inventory account the cost was capitalised into, recorded at posting');

            // Evidence. Stored on a private disk, never under the web root.
            $table->string('evidence_path', 255)->nullable();
            $table->string('evidence_filename', 191)->nullable();
            $table->string('evidence_mime', 100)->nullable();
            $table->unsignedBigInteger('evidence_bytes')->nullable();
            $table->char('evidence_hash', 64)->nullable()->comment('SHA-256 of the stored bytes');
            $table->foreignId('evidence_uploaded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('evidence_uploaded_at')->nullable();

            // Actor history. Each step records who and when, and a rejection
            // records why — a refusal with no reason tells the claimant nothing.
            $table->foreignId('submitted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Segregation of duties, waived visibly or not at all.
            $table->boolean('sod_exception')->default(false);
            $table->text('sod_exception_reason')->nullable();

            $table->foreignId('posted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('posted_at')->nullable();

            // Payment is a separate fact from posting. An expense posted against
            // accrued expenses payable is a real cost the company has not yet
            // paid, and the difference matters.
            $table->string('payment_status', 20)->default('unpaid')->comment('unpaid|paid');
            $table->foreignId('payment_journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('paid_at')->nullable();

            // Corrections are replacements, never edits.
            $table->foreignId('reverses_expense_id')->nullable()->constrained('gold_trading_expenses')->nullOnDelete();
            $table->foreignId('reversed_by_expense_id')->nullable()->constrained('gold_trading_expenses')->nullOnDelete();
            $table->text('reversal_reason')->nullable();

            $table->text('notes')->nullable();

            $table->unique('reference');
            $table->index(['status', 'expense_date']);
            $table->index(['payment_status', 'status']);

            // The same invoice from the same supplier, twice. MySQL treats NULLs
            // in a unique index as distinct, so expenses with no supplier
            // reference are unconstrained — which is right, because there is
            // nothing to compare them on.
            $table->unique(['payee_name', 'external_ref'], 'gold_expenses_supplier_invoice_unique');
        });

        // Costs entered before there was a workflow. They are facts about the
        // deals they belong to, so they keep counting; they are simply not
        // claimed to have been approved by anyone.
        DB::table('gold_trading_expenses')->update(['status' => 'recorded']);
    }

    public function down(): void
    {
        Schema::table('gold_trading_expenses', function (Blueprint $table) {
            $table->dropUnique('gold_expenses_supplier_invoice_unique');
            $table->dropIndex(['payment_status', 'status']);
            $table->dropIndex(['status', 'expense_date']);
            $table->dropUnique(['reference']);

            $table->dropConstrainedForeignId('reversed_by_expense_id');
            $table->dropConstrainedForeignId('reverses_expense_id');
            $table->dropConstrainedForeignId('paid_by');
            $table->dropConstrainedForeignId('payment_journal_id');
            $table->dropConstrainedForeignId('posted_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('evidence_uploaded_by');

            $table->dropColumn([
                'reference', 'status', 'payee_name', 'external_ref', 'capitalised', 'capitalised_into',
                'evidence_path', 'evidence_filename', 'evidence_mime', 'evidence_bytes', 'evidence_hash',
                'evidence_uploaded_at', 'submitted_at', 'approved_at', 'rejected_at', 'rejection_reason',
                'sod_exception', 'sod_exception_reason', 'posted_at', 'payment_status', 'paid_at',
                'reversal_reason', 'notes',
            ]);
        });
    }
};
