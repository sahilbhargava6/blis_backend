<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'niche_field',
        'postback_url',
        'group_id',
        'pending_balance',
        'cleared_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function links()
    {
        return $this->hasMany(Link::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }
}
