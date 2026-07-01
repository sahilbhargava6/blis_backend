<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'master_url',
        'total_payout',
        'split_member_percent',
        'split_leader_percent',
        'is_active',
    ];

    public function links()
    {
        return $this->hasMany(Link::class);
    }
}
