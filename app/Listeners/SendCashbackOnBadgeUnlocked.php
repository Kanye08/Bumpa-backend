<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use App\Services\MockPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCashbackOnBadgeUnlocked implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly MockPaymentService $paymentService) {}

    /**
     * Handle the event.
     */
    public function handle(BadgeUnlocked $event): void
    {
        $this->paymentService->processCashback(
            $event->user,
            "Badge unlocked: {$event->badgeName}"
        );
    }
}
