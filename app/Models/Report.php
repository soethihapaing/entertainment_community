<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_date' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id', 'user_id');
    }

    /**
     * Polymorphic target resolution
     */
    public function getTargetEntityAttribute()
    {
        return match ($this->target_type) {
            'user' => User::find($this->target_id),
            'review' => Review::find($this->target_id),
            'discussion_topic' => DiscussionTopic::find($this->target_id),
            'discussion_comment' => DiscussionComment::find($this->target_id),
            default => null,
        };
    }
}
