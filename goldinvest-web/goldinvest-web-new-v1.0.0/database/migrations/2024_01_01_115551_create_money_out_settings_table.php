<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('money_out_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('c_balance')->default(false)->comment('Current/Wallet Balance');
            $table->boolean('p_balance')->default(true)->comment('Profit Balance');
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
        Schema::dropIfExists('money_out_settings');
    }
};
