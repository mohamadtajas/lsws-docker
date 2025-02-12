<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function Service()
    {
        $decorator =  'App\Services\\' . $this->name . 'Service';
        if (class_exists($decorator)) {
            return (new $decorator);
        } else {
            return null;
        }
    }
}
