<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePollQuestionAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poll_question_answers', function (Blueprint $table) {
            $table->increments('id');

            $table->string('answer');

            $table->unsignedInteger('vote_id');
            $table->foreign('vote_id')
                ->references('id')
                ->on('votes');

            $table->unsignedInteger('poll_question_id');
            $table->foreign('poll_question_id')
                ->references('id')
                ->on('poll_questions');
            
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
        Schema::dropIfExists('poll_question_answers');
    }
}
