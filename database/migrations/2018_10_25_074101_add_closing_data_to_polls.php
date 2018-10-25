<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClosingDataToPolls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('polls', function (Blueprint $table) {
            //String Bool value of if the poll is closed or not
            //'True' or 'False'
            $table->string('isClosed')->default('False');

            //Closing date of the poll
            $table->string('closingDate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
