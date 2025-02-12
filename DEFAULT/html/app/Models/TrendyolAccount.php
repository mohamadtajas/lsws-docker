<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrendyolAccount extends Model
{
    protected $fillable = [
        'user_id' , 'user_email','first_name', 'last_name', 'user_name', 'password', 'session', 'session_time'
    ];
}
