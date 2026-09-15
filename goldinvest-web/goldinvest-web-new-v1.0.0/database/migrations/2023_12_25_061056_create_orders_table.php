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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('gold_stock_id');
            $table->integer('quantity');
            $table->string('mobile_code');
            $table->string('mobile');
            $table->string('full_mobile');
            $table->text('address');
            $table->decimal('total_amount', 28, 8);
            $table->enum('payment_type',[
                GlobalConst::PAYMENT_TYPE_USER_WALLET,
                GlobalConst::PAYMENT_TYPE_CASH_ON_DELIVERY,
            ]);
            $table->boolean('status')->default(false);
            $table->tinyInteger('order_status')->default(0)->comment("0: Default, 1: Accepted, 2: OnGoing, 3: Delivered, 4: Cancelled");
            $table->text('cancel_reason')->nullable();
            $table->boolean('payment_status')->default(false);
            $table->timestamps();

            $table->foreign("gold_stock_id")->references("id")->on("gold_stocks")->onDelete("cascade")->onUpdate("cascade");
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade")->onUpdate("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
