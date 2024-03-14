<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'advertiser_id',
        'ad_name',
        'publish_date',
        'location',
        'mile_radius',
        'address',
        'city',
        'state',
        'country',
        'zip',
        'duration_price',
        'expired',
        'expired_at',
        'renew',
        'renew_at',
        'paused',
        'paused_at',
        'deleted',
    ];

    protected $casts = [
        'duration_price' => 'array',
    ];
    protected $dates = ['expired_at', 'renew_at'];

    public function availabilities()
    {
        return $this->hasMany(Availability::class, 'ad_id');
    }
}
