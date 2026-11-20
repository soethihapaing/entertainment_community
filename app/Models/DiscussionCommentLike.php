<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscussionCommentLike extends Model
{
    protected $table = 'discussion_comment_likes';
    protected $primaryKey = 'comment_like_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_date' => 'datetime',
    ];

    public function comment()
    {
        return $this->belongsTo(DiscussionComment::class, 'comment_id', 'comment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
