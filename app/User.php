<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Storage;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $appends = ['avatarurl', 'tags'];

    public function getAvatarurlAttribute()
    {
        return Storage::url('avatars/'.$this->id.'/avatar.png');
    }

    public function getTagsAttribute()
    {
        return $this->tags()->get();
    }
/*
    public function getInteresseparAttribute(){
        if($this->specialiste)return $this->interessepar()->get();
        else return null;
    }
*/
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name', 
        'email', 
        'password',
        'specialiste',
        'tel',
        'salaire',
        'excellence',
        'avatar',
        'active', 
        'activation_token',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 
        'remember_token', 
        'active',
        'email_verified_at',
        'created_at', 
        'updated_at',
        'deleted_at',
        'activation_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime', 
        'nom' => 'string',
        'email' => 'string',
        'specialiste' => 'boolean',
        'salaire' => 'float',
        'excellence' => 'integer',
    ];
    public function tags()
    {
        return $this->belongsToMany('App\Tag','tag_user','user_id','tag_id');
    }

    public function interessepar()
    {
        return $this->belongsToMany('App\Demmande','user_demmande','user_id','demmande_id');
    }

    public function demmandes()
    {
        return $this->hasMany('App\Demmande');
    }

}
