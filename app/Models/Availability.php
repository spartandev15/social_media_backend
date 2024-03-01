<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'advertiser_id',
        'ad_id',
        'dates',
    ];

    protected $casts = [
        'dates' => 'array',
    ];
}
