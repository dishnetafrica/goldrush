<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3G: the two things reporting is allowed to write.
 *
 * Reports read the general ledger and the recorded results; they post nothing
 * and change nothing. The only marks they leave are these: a record of who
 * looked at what, with the control outcomes they were shown, and the period
 * close pack - one PDF per close, stored privately and hashed, which is a
 * snapshot of what the books said and never an accounting record itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_report_views', function (Blueprint $table) {
            $table->id();
            $table->string('report', 40)->comment('trial-balance|profit-loss|balance-sheet|cash-flow|gold-trading|investor-liability|period-close|dashboard|controls|close-pack');
            $table->string('viewer_type', 10)->comment('admin|user|console');
            $table->unsignedBigInteger('viewer_id')->nullable();
            $table->json('scope')->comment('period, dates, as-at, lot, user, source period as rendered');
            $table->boolean('interim')->default(true);
            $table->json('controls')->comment('every control the report evaluated and its outcome');
            $table->boolean('controls_passed')->default(true);
            $table->string('export_format', 10)->nullable()->comment('pdf|csv when this was an export');
            $table->char('file_hash', 64)->nullable()->comment('SHA-256 of the exported bytes');
            $table->dateTime('rendered_at');
            $table->timestamps();

            $table->index(['report', 'rendered_at']);
            $table->index(['viewer_type', 'viewer_id']);
        });

        Schema::create('period_close_packs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique()->comment('PCK-YYYYMMDD-NNNNNN');
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('close_reference', 40)->comment('The PCL reference of the close this pack documents');
            $table->string('file_path');
            $table->char('file_hash', 64);
            $table->unsignedBigInteger('file_bytes');
            $table->json('controls')->comment('control outcomes at generation');
            $table->boolean('controls_passed');
            $table->json('sections')->comment('which reports the pack contains');
            $table->string('generated_by', 60)->comment('admin username or console');
            $table->dateTime('generated_at');
            $table->timestamps();

            // One pack per close. A reopened and re-closed period has a new PCL
            // reference and therefore gets a new pack; the old one stays.
            $table->unique(['accounting_period_id', 'close_reference'], 'period_close_packs_one_per_close');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_close_packs');
        Schema::dropIfExists('accounting_report_views');
    }
};
