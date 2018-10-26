<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PollQuestionAnswer extends Model
{
    //
    public function vote()
    {
        return $this->belongsTo('App\Vote');
    }

    public function question()
    {
        return $this->belongsTo('App\PollQuestion');
    }
}
