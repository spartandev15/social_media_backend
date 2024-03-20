<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasUlids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'age',
        'age_confirmed',
        'gender',
        'ethnicity',
        'height',
        'breast_size',
        'eye_color',
        'hair_color',
        'body_type',
        'profile_photo',
        'cover_photo',
        'street_address',
        'city',
        'state',
        'country',
        'zip',
        'payment_received',
        'email_verified',
        'role',
        'plan',
        'deleted_by',
    ];

    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function advertisements()
    {
        return $this->hasMany(AdvertiserPhoto::class, 'advertiser_id');
    }

    public function availabilities()
    {
        return $this->hasMany(Availability::class, 'advertiser_id');
    }

    public function advertiserPhotos()
    {
        return $this->hasMany(AdvertiserPhoto::class, 'advertiser_id');
    }

    public function advertiserVideos()
    {
        return $this->hasMany(AdvertiserVideo::class, 'advertiser_id');
    }
}
