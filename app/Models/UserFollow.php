<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
    protected $table = 'user_follows';
    protected $primaryKey = 'follow_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_date' => 'datetime',
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id', 'user_id');
    }

    public function following()
    {
        return $this->belongsTo(User::class, 'following_id', 'user_id');
    }
}
