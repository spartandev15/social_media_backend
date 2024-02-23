<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertiserPhoto extends Model
{
    use HasFactory;
    protected $fillable = [
        'advertiser_id',
        'image',
    ];
}
