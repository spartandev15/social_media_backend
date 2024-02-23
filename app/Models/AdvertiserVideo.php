<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertiserVideo extends Model
{
    use HasFactory;
    protected $fillable = [
        'advertiser_id',
        'video',
    ];
}
