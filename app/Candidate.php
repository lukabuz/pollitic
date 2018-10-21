<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    //
    public function votes()
    {
        return $this->hasMany('App\Vote');
    }
}
