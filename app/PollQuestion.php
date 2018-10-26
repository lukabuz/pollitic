<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PollQuestion extends Model
{
    //
    public function poll()
    {
        return $this->belongsTo('App\Poll');
    }

    public function answers()
    {
        return $this->hasMany('App\PollQuestionAnswer');
    }
}
