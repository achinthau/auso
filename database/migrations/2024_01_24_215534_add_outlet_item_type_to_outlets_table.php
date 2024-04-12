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
        Schema::connection('mysql')->table('outlets', function (Blueprint $table) {
            $table->string('outlet_item_type')->nullable()->comment(' values [old , new]');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql')->table('outlets', function (Blueprint $table) {
            $table->dropColumn('outlet_item_type')->nullable()->comment(' values [old , new]');
        });
    }
};
