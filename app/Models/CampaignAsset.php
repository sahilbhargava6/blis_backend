<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'name',
        'type',
        'content',
        'description',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
