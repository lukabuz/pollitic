<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->increments('id');

            $table->longText('name');
            $table->string('description');
            $table->string('imageLink')->nullable();

            //A Serialized array of what charts to show.
            //These values are provided and parsed by the front-end
            $table->longText('charts');

            //Does the poll require mobile phone authentication
            //'True' or 'False'
            $table->string('requirePhoneAuth');

            //Does the poll require mobile phone authentication
            //'True' or 'False'
            $table->string('isListed');

            //Cookie value used for spam protection
            $table->string('cookieValue');
            
            $table->timestamps();
        });

        Schema::table('votes', function (Blueprint $table) {
            //Foreign key relation for poll
            $table->unsignedInteger('poll_id');
            $table->foreign('poll_id')
                ->references('id')
                ->on('polls')
                ->onDelete('cascade');
        });

        Schema::table('candidates', function (Blueprint $table) {
            //Foreign key relation for poll
            $table->unsignedInteger('poll_id');
            $table->foreign('poll_id')
                ->references('id')
                ->on('polls')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('polls');
    }
}
