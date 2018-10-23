<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    //
    public function voteCount(){
        return Vote::where('status', 'verified')->where('candidate_id', $this->id)->count();
    }

    public function votes()
    {
        return $this->hasMany('App\Vote');
    }

    //
    public function poll(){
        return $this->belongsTo('App\Poll');
    }
}
