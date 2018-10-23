<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{   
    public $timestamps = false;
    protected $hidden = array('pin', 'number', 'status');

    //
    public function candidate(){
        return $this->belongsTo('App\Candidate');
    }

    //
    public function poll(){
        return $this->belongsTo('App\Poll');
    }
}
