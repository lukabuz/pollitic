<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
}
