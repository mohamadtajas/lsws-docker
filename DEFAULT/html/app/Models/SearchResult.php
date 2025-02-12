<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchResult extends Model
{
  protected $fillable = [
        'keyword', 'user_id'
    ];
}
