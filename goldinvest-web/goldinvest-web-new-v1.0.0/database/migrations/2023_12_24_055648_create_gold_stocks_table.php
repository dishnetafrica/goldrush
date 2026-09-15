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
        Schema::create('gold_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->longText("title");
            $table->string("type");
            $table->decimal("price", 8, 2);
            $table->decimal("charge", 8, 2);
            $table->string('weight');
            $table->string('purity');
            $table->string('manufacturer');
            $table->string('country_of_origin');
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
        Schema::dropIfExists('gold_stocks');
    }
};
