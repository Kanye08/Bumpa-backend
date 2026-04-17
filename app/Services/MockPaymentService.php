<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class MockPaymentService
{
    private int $cashbackAmount;

    public function __construct()
    {
        $this->cashbackAmount = (int) config('loyalty.cashback_amount', 300);
    }

    /**
     * Process a cashback payment for the given user.
     */
    public function processCashback(User $user, string $reason = 'Badge unlocked'): array
    {
        $reference = 'CASHBACK-' . strtoupper(uniqid());

        // Simulate payment processing delay
        $result = [
            'status'    => 'success',
            'reference' => $reference,
            'amount'    => $this->cashbackAmount,
            'currency'  => 'NGN',
            'user_id'   => $user->id,
            'user_email'=> $user->email,
            'reason'    => $reason,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('stack')->info('[MockPayment] Cashback processed', $result);

        return $result;
    }
}
