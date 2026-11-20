<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscussionLike extends Model
{
    protected $table = 'discussion_likes';
    protected $primaryKey = 'discussion_like_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_date' => 'datetime',
    ];

    public function topic()
    {
        return $this->belongsTo(DiscussionTopic::class, 'topic_id', 'topic_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
