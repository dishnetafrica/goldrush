<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company-side gold trading tables.
 *
 * Additive only: no existing GoldInvest table is touched. Investor money stays a
 * company liability in user_wallets/transactions; gold is a company asset here.
 * The only bridge between the two sides is gold_capital_allocations, which records
 * whose capital funded a lot WITHOUT moving any investor balance.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A purchase of physical gold, in the local currency it was bought with.
        Schema::create('gold_lots', function (Blueprint $table) {
            $table->id();
            $table->string('lot_code', 50)->unique();
            $table->date('purchase_date');
            $table->string('project_name', 150)->nullable();
            $table->string('location', 200)->nullable();
            $table->string('supplier_name', 150)->nullable();
            $table->string('purity_in', 30)->nullable()->comment('Purity as received, e.g. "unrefined", "22K"');
            $table->decimal('gross_grams', 20, 4);
            $table->string('purchase_currency', 10)->default('USD');
            $table->decimal('price_per_gram_local', 24, 8);
            $table->decimal('fx_rate_to_usd', 20, 8)->default(1)->comment('Local currency units per 1 USD on the purchase date');
            $table->decimal('price_per_gram_usd', 20, 8);
            $table->decimal('total_cost_local', 24, 8);
            $table->decimal('total_cost_usd', 20, 8);
            $table->string('reference_rate_note', 191)->nullable()->comment('International rate quoted at purchase time, for context');
            $table->string('status', 30)->default('purchased')->comment('purchased|processing|in_stock|partially_sold|sold|written_off');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->index(['purchase_date', 'status']);
        });

        // Refining / cleaning. Waste is a real loss of grams and must be recorded.
        Schema::create('gold_processings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gold_lot_id')->constrained('gold_lots')->cascadeOnDelete();
            $table->date('processed_at');
            $table->string('method', 150)->nullable()->comment('e.g. "cleaning to 24KT"');
            $table->decimal('input_grams', 20, 4);
            $table->decimal('waste_grams', 20, 4)->default(0);
            $table->decimal('waste_percent', 9, 4)->default(0);
            $table->decimal('output_grams', 20, 4);
            $table->string('output_purity', 30)->nullable()->comment('e.g. "24K"');
            $table->decimal('cost_usd', 20, 8)->default(0)->comment('Refining cost, if charged separately');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->index('processed_at');
        });

        // A sale out of a lot. Partial sales are supported.
        Schema::create('gold_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_code', 50)->unique();
            $table->foreignId('gold_lot_id')->constrained('gold_lots')->restrictOnDelete();
            $table->date('sale_date');
            $table->string('buyer_name', 150)->nullable();
            $table->string('location', 200)->nullable();
            $table->decimal('grams_sold', 20, 4);
            $table->decimal('reference_rate_usd', 20, 8)->nullable()->comment('International price per gram used as the basis');
            $table->decimal('discount_percent', 9, 4)->nullable()->comment('Discount applied to the reference rate');
            $table->string('price_basis', 191)->nullable()->comment('Human readable basis, e.g. "international less 10%"');
            $table->decimal('price_per_gram_usd', 20, 8);
            $table->decimal('gross_proceeds_usd', 20, 8);
            $table->string('settlement_currency', 10)->default('USD');
            $table->decimal('fx_rate_to_usd', 20, 8)->default(1);
            $table->decimal('gross_proceeds_local', 24, 8)->nullable();
            $table->string('status', 30)->default('settled')->comment('draft|settled|cancelled');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->index(['sale_date', 'status']);
        });

        // Trading and operating costs. Attach to a lot and/or a sale where possible.
        Schema::create('gold_trading_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->string('category', 50)->comment('transport|refining|assay|security|commission|travel|operating|other');
            $table->string('description', 191);
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('amount_local', 24, 8);
            $table->decimal('fx_rate_to_usd', 20, 8)->default(1);
            $table->decimal('amount_usd', 20, 8);
            $table->foreignId('gold_lot_id')->nullable()->constrained('gold_lots')->nullOnDelete();
            $table->foreignId('gold_sale_id')->nullable()->constrained('gold_sales')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->index(['expense_date', 'category']);
        });

        // Which investor capital funded which lot. Attribution only: it never moves a wallet balance.
        Schema::create('gold_capital_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gold_lot_id')->constrained('gold_lots')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('source_trx_id', 100)->nullable()->comment('transactions.trx_id that brought this money in');
            $table->decimal('amount_usd', 20, 8);
            $table->date('allocated_at');
            $table->string('status', 30)->default('allocated')->comment('allocated|returned|written_off');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // Frozen result of a closed lot. Written once the lot is fully sold and reviewed.
        Schema::create('gold_lot_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gold_lot_id')->unique()->constrained('gold_lots')->cascadeOnDelete();
            $table->date('closed_at');
            $table->decimal('refined_grams', 20, 4);
            $table->decimal('sold_grams', 20, 4);
            $table->decimal('cost_of_goods_sold_usd', 20, 8);
            $table->decimal('expenses_usd', 20, 8);
            $table->decimal('proceeds_usd', 20, 8);
            $table->decimal('gross_profit_usd', 20, 8);
            $table->decimal('net_profit_usd', 20, 8);
            $table->decimal('investor_share_percent', 9, 4)->default(0)->comment('Policy decision: share of net profit owed to investors');
            $table->decimal('investor_profit_usd', 20, 8)->default(0);
            $table->decimal('company_profit_usd', 20, 8)->default(0);
            $table->string('status', 30)->default('draft')->comment('draft|approved|distributed');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_lot_results');
        Schema::dropIfExists('gold_capital_allocations');
        Schema::dropIfExists('gold_trading_expenses');
        Schema::dropIfExists('gold_sales');
        Schema::dropIfExists('gold_processings');
        Schema::dropIfExists('gold_lots');
    }
};
