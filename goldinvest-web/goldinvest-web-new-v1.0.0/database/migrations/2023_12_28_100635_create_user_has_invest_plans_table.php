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
        Schema::create('user_has_invest_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('invest_plan_id');
            $table->decimal('invest_amount',28,8)->default(0);
            $table->timestamp('exp_at');
            $table->tinyInteger('status')->default(GlobalConst::RUNNING)->comment("1: Complete, 2: Running, 3: Cancel");
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('invest_plan_id')->references('id')->on('investment_plans')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_has_invest_plans');
    }
};
