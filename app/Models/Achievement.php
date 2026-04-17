<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'required_purchase_count'  => 'integer',
        'required_purchase_amount' => 'decimal:2',
    ];

    /**
     * Users who have unlocked this achievement.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withTimestamps()
            ->withPivot('unlocked_at');
    }

    /**
     * All defined achievements in order of difficulty.
     */
    public static function allOrdered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('required_purchase_count')->get();
    }
}
