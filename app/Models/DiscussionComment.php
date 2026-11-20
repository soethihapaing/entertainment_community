<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscussionComment extends Model
{
    protected $table = 'discussion_comments';
    protected $primaryKey = 'comment_id';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $guarded = [];

    protected $casts = [
        'contains_spoiler' => 'boolean',
        'is_deleted' => 'boolean',
        'deleted_date' => 'datetime',
    ];

    public function topic()
    {
        return $this->belongsTo(DiscussionTopic::class, 'topic_id', 'topic_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(DiscussionComment::class, 'parent_comment_id', 'comment_id');
    }

    public function replies()
    {
        return $this->hasMany(DiscussionComment::class, 'parent_comment_id', 'comment_id');
    }

    public function likes()
    {
        return $this->hasMany(DiscussionCommentLike::class, 'comment_id', 'comment_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'user_id');
    }
}
