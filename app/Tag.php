<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'nom',
    ];
    
    protected $hidden = [
        'created_at', 'updated_at', 'pivot'
    ];

}
