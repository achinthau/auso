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
        Schema::connection('mysql')->table('ticket_items', function (Blueprint $table) {
            $table->string('item_remarks', 30)->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql')->table('ticket_items', function (Blueprint $table) {
            $table->string('item_remarks', 30)->nullable()->after('qty');
        });
    }
};
