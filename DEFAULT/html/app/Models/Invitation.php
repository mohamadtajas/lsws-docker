<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;
    protected $fillable = [
        'invited_user',
        'invited_by_user',
        'used'
    ];

    public function user(){
        return $this->belongsTo(User::class , 'invited_by_user' , 'email');
    }
}
