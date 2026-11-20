<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'review_id';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $guarded = [];

    protected $casts = [
        'rating' => 'decimal:1',
        'contains_spoiler' => 'boolean',
        'is_deleted' => 'boolean',
        'deleted_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function title()
    {
        return $this->belongsTo(Title::class, 'title_id', 'title_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'user_id');
    }

    public function likes()
    {
        return $this->hasMany(ReviewLike::class, 'review_id', 'review_id');
    }
}
