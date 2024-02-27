<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

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
        
    ];

    protected $casts = [
        'duration_price' => 'array',
    ];
    protected $dates = ['expired_at', 'renew_at'];
}
