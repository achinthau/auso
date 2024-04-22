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
        Schema::create('pos_order_reference', function (Blueprint $table) {
            $table->id();
            $table->string('function')->nullable();
            $table->string('tran_id')->nullable();
            $table->string('order_ref');
            $table->string('bill_ref')->nullable();
            $table->string('sender_id');
            $table->string('receiver_id');
            $table->string('order_status');
            $table->boolean('success');
            $table->text('message')->nullable();
            $table->date('tran_date')->nullable();
            $table->time('tran_time')->nullable();
            $table->string('client_id')->nullable();
            $table->string('biz_type')->nullable();
            $table->string('loc_id')->nullable();
            $table->string('tran_type')->nullable();
            $table->json('data')->nullable();
            $table->string('res_type')->nullable();
            $table->string('retry_count')->nullable();
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
        Schema::dropIfExists('pos_order_reference');
    }
};
