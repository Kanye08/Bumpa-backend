<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;

class AchievementController extends Controller
{
    public function __construct(private readonly LoyaltyService $loyaltyService) {}

    /**
     * GET /api/users/{user}/achievements
     *
     * Returns the full loyalty dashboard data for a given user.
     */
    public function show(User $user): JsonResponse
    {
        $summary = $this->loyaltyService->getUserAchievementSummary($user);

        return response()->json($summary);
    }
}
