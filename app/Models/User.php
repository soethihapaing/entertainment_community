<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $guarded = [];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Use password_hash column for authentication
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Check if user is active administrator
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin' && $this->account_status === 'active';
    }

    // Relationships
    public function titlesSubmitted()
    {
        return $this->hasMany(Title::class, 'Submitted_by', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }

    public function topics()
    {
        return $this->hasMany(DiscussionTopic::class, 'user_id', 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(DiscussionComment::class, 'user_id', 'user_id');
    }

    public function watchlist()
    {
        return $this->hasMany(UserWatchlist::class, 'user_id', 'user_id');
    }

    public function followers()
    {
        return $this->hasMany(UserFollow::class, 'following_id', 'user_id');
    }

    public function following()
    {
        return $this->hasMany(UserFollow::class, 'follower_id', 'user_id');
    }

    public function reportsSubmitted()
    {
        return $this->hasMany(Report::class, 'reporter_id', 'user_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(AdminActivityLog::class, 'admin_id', 'user_id');
    }
}
