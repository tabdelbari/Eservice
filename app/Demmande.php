<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Demmande extends Model
{
    protected $appends = ['tags','specialistes','interesse'];

    public function getTagsAttribute()
    {
        return $this->tags()->get();
    }
    public function getInteresseAttribute()
    {
        if($this->specialiste()->find(Auth::user()->id))return true;
        else return false;
    }

    public function getSpecialistesAttribute(){
        return $this->specialiste()->get();
    }

    protected $fillable = [
        'user_id',
        'titre',
        'description',
        'etat',
        'lat',
        'lng',
        'estimation',
    ];

    protected $hidden = [
        'user_id', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'titre' => 'string',
        'description' => 'string',
        'etat' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
        'estimation' => 'float',
    ];
    //
    public function tags()
    {
        return $this->belongsToMany('App\Tag','tag_demmande','demmande_id','tag_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function specialiste()
    {
        return $this->belongsToMany('App\User','user_demmande','demmande_id','user_id');
    }
}
