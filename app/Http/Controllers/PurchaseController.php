<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function __construct(private readonly LoyaltyService $loyaltyService) {}

    /**
     * POST /api/purchases
     *
     * Records a new purchase for the authenticated user and
     * triggers the achievement/badge evaluation pipeline.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'    => 'required|numeric|min:1',
            'reference' => 'nullable|string|max:100|unique:purchases,reference',
        ]);

        $user = Auth::user();

        $purchase = Purchase::create([
            'user_id'   => $user->id,
            'amount'    => $validated['amount'],
            'reference' => $validated['reference'] ?? 'REF-' . strtoupper(uniqid()),
        ]);

        // Run achievement/badge checks after recording the purchase
        $this->loyaltyService->processUserPurchase($user);

        return response()->json([
            'message'  => 'Purchase recorded successfully.',
            'purchase' => $purchase,
            'summary'  => $this->loyaltyService->getUserAchievementSummary($user),
        ], 201);
    }

    /**
     * GET /api/purchases
     *
     * Returns all purchases for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $purchases = Auth::user()
            ->purchases()
            ->latest()
            ->get(['id', 'amount', 'reference', 'created_at']);

        return response()->json([
            'purchases'    => $purchases,
            'total_count'  => $purchases->count(),
            'total_amount' => $purchases->sum('amount'),
        ]);
    }
}
