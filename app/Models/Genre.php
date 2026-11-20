<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $table = 'genres';
    protected $primaryKey = 'genre_id';
    public $timestamps = false;

    protected $guarded = [];

    public function titles()
    {
        return $this->belongsToMany(Title::class, 'title_genres', 'genre_id', 'title_id');
    }
}
