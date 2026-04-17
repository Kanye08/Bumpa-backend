<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'required_achievements_count' => 'integer',
    ];

    /**
     * Users who have earned this badge.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withTimestamps()
            ->withPivot('earned_at');
    }

    /**
     * All defined badges ordered by required achievements (easiest first).
     */
    public static function allOrdered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('required_achievements_count')->get();
    }
}
