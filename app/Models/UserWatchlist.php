<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWatchlist extends Model
{
    protected $table = 'user_watchlist';
    protected $primaryKey = 'watchlist_id';

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_date';

    protected $guarded = [];

    protected $casts = [
        'started_date' => 'datetime',
        'completed_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function title()
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }
}
