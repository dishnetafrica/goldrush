<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records when a deal's expenses are known to be complete.
 *
 * Until this is set, a deal's net trading result is whatever has been recorded
 * so far and can still move as further costs come in. Nothing in the existing
 * data could stand in for it: a lot whose profit has been distributed looks
 * finished, but both existing deals were distributed while their expense
 * capture was still incomplete, so reading "closed" from that would assert
 * something untrue.
 *
 * Nothing sets this yet. Phase 3's period close will, through an approval step.
 * Until then every deal reads as open with expenses pending, which is accurate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gold_lots', function (Blueprint $table) {
            $table->dateTime('expenses_finalised_at')->nullable()->after('expense_policy')
                ->comment('Set only when expense capture for this deal is confirmed complete');
            $table->foreignId('expenses_finalised_by')->nullable()->after('expenses_finalised_at')
                ->constrained('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gold_lots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expenses_finalised_by');
            $table->dropColumn('expenses_finalised_at');
        });
    }
};
