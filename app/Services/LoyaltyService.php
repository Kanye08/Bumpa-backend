<?php

namespace App\Services;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public function __construct(
        private readonly MockPaymentService $paymentService
    ) {}

    /**
     * Called after a purchase is recorded. Checks and unlocks any newly
     * eligible achievements and badges for the given user.
     */
    public function processUserPurchase(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->checkAndUnlockAchievements($user);
            $this->checkAndUnlockBadges($user);
        });
    }


    private function checkAndUnlockAchievements(User $user): void
    {
        $purchaseCount  = $user->totalPurchaseCount();
        $purchaseAmount = $user->totalPurchaseAmount();

        // Fetch IDs already unlocked so we can skip them
        $unlockedIds = $user->achievements()->pluck('achievements.id');

        $eligible = Achievement::allOrdered()
            ->whereNotIn('id', $unlockedIds)
            ->filter(fn (Achievement $a) =>
                $purchaseCount  >= $a->required_purchase_count &&
                $purchaseAmount >= $a->required_purchase_amount
            );

        foreach ($eligible as $achievement) {
            $user->achievements()->attach($achievement->id, [
                'unlocked_at' => Carbon::now(),
            ]);

            event(new AchievementUnlocked($achievement->name, $user));
        }
    }

    private function checkAndUnlockBadges(User $user): void
    {
        // Re-query after achievements may have been added above
        $unlockedAchievementCount = $user->achievements()->count();

        $earnedBadgeIds = $user->badges()->pluck('badges.id');

        $eligibleBadges = Badge::allOrdered()
            ->whereNotIn('id', $earnedBadgeIds)
            ->filter(fn (Badge $b) =>
                $unlockedAchievementCount >= $b->required_achievements_count
            );

        foreach ($eligibleBadges as $badge) {
            $user->badges()->attach($badge->id, [
                'earned_at' => Carbon::now(),
            ]);

            event(new BadgeUnlocked($badge->name, $user));

            // Trigger cashback for each new badge earned
            $this->paymentService->processCashback(
                $user,
                "Badge unlocked: {$badge->name}"
            );
        }
    }

    //Dashboard Data
    public function getUserAchievementSummary(User $user): array
    {
        $allAchievements    = Achievement::allOrdered();
        $unlockedIds        = $user->achievements()->pluck('achievements.id')->toArray();
        $allBadges          = Badge::allOrdered();
        $unlockedAchCount   = count($unlockedIds);

        // Unlocked achievements
        $unlocked = $allAchievements
            ->whereIn('id', $unlockedIds)
            ->pluck('name')
            ->values()
            ->toArray();

        // Next available (not yet unlocked but within reach of next milestone)
        $locked = $allAchievements->whereNotIn('id', $unlockedIds);
        $nextAvailable = $locked->pluck('name')->values()->toArray();

        // Current badge: the highest badge the user has earned
        $currentBadge = $user->badges()
            ->orderByPivot('earned_at', 'desc')
            ->first();

        // Next badge: the next one not yet earned
        $earnedBadgeIds = $user->badges()->pluck('badges.id')->toArray();
        $nextBadge = $allBadges->whereNotIn('id', $earnedBadgeIds)->first();

        $remainingToUnlockNextBadge = $nextBadge
            ? max(0, $nextBadge->required_achievements_count - $unlockedAchCount)
            : 0;

        return [
            'unlocked_achievements' => $unlocked,
            'next_available_achievements' => $nextAvailable,
            'current_badge' => $currentBadge?->name ?? 'None',
            'next_badge' => $nextBadge?->name ?? 'None — max level reached!',
            'remaining_to_unlock_next_badge'  => $remainingToUnlockNextBadge,
        ];
    }
}
