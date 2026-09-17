<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records when the segregation-of-duties rule was waived on a reconciliation.
 *
 * The rule exists because a reconciliation signed off by the only person who
 * has seen the evidence proves nothing. A single-admin environment cannot meet
 * it, so it can be relaxed — but a waived control has to leave a mark, or the
 * next person reading the books cannot tell which reconciliations were checked
 * by two people and which by one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->boolean('sod_exception')->default(false)->after('completed_at')
                ->comment('1 when the importer also signed this off, under an explicit configuration');
            $table->text('sod_exception_reason')->nullable()->after('sod_exception');
        });
    }

    public function down(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->dropColumn(['sod_exception', 'sod_exception_reason']);
        });
    }
};
