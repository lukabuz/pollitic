<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Vote;

class Poll extends Model
{
    //
    public function candidates()
    {
        return $this->hasMany('App\Candidate');
    }

    public function votes()
    {
        return $this->hasMany('App\Vote');
    }

    public function totalVotes(){
        return Vote::where('status', 'verified')->where('poll_id', $this->id)->count();
    }
}
