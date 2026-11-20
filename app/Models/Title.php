<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Title extends Model
{
    protected $table = 'titles';
    protected $primaryKey = 'title_id';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $guarded = [];

    protected $casts = [
        'release_date' => 'date',
        'Reviewed_date' => 'datetime',
        'is_hidden' => 'boolean',
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'title_genres', 'title_id', 'genre_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'Submitted_by', 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'Reviewed_by', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'title_id', 'title_id');
    }

    public function topics()
    {
        return $this->hasMany(DiscussionTopic::class, 'title_id', 'title_id');
    }

    public function watchlistEntries()
    {
        return $this->hasMany(UserWatchlist::class, 'title_id', 'title_id');
    }
}
