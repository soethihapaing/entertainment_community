<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscussionTopic extends Model
{
    protected $table = 'discussion_topics';
    protected $primaryKey = 'topic_id';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $guarded = [];

    protected $casts = [
        'contains_spoiler' => 'boolean',
        'is_deleted' => 'boolean',
        'deleted_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function title()
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }

    public function comments()
    {
        return $this->hasMany(DiscussionComment::class, 'topic_id', 'topic_id');
    }

    public function likes()
    {
        return $this->hasMany(DiscussionLike::class, 'topic_id', 'topic_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'user_id');
    }
}
