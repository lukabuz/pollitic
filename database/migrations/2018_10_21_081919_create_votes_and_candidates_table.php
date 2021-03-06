<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVotesAndCandidatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   
        Schema::create('candidates', function (Blueprint $table) {
            $table->increments('id');

            //Hashed phone number
            $table->string('name');
            $table->string('number')->nullable();;
            $table->string('websiteLink')->nullable();;
            $table->string('socialMediaLink')->nullable();;
            $table->string('imageLink')->nullable();;

            $table->timestamps();
        });


        Schema::create('votes', function (Blueprint $table) {
            $table->increments('id');

            //Hashed phone number
            $table->string('number');

            //Hashed PIN Number
            $table->string('pin');

            //Status of vote
            //'unverified' : has not verified phone # yet
            //'verified' : has verified phone #
            $table->string('status');

            //Extra optional information
            $table->string('gender')->nullable();
            $table->integer('age')->nullable();

            //Foreign key relation for candidate the voter chose
            $table->unsignedInteger('candidate_id');
            $table->foreign('candidate_id')
                ->references('id')
                ->on('candidates')
                ->onDelete('cascade');

            //timestamps could be used for identification so they are removed
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('votes');
    }
}
