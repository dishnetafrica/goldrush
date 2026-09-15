<?php

use App\Constants\GlobalConst;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->longText("data");
            $table->integer("plan_duration");
            $table->enum('profit_return_type',[
                GlobalConst::INVEST_PROFIT_DAILY_BASIS,
                GlobalConst::INVEST_PROFIT_ONE_TIME,
            ]);
            $table->decimal("minimum_investment", 28, 8);
            $table->decimal("minimum_investment_offer", 28, 8)->nullable();
            $table->decimal("maximum_investment", 28, 8);
            $table->decimal("profit", 28, 8);
            $table->decimal("profit_percentage", 28, 8);
            $table->string('image');
            $table->boolean("status")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('investment_plans');
    }
};
