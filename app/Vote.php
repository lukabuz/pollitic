<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $hidden = array('pin', 'number', 'created_at', 'updated_at', 'status');

    //
    public function candidate(){
        return $this->belongsTo('App\Candidate');
    }
}
