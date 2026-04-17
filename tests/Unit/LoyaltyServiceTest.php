<?php

namespace Tests\Unit;

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\User;
use App\Services\LoyaltyService;
use App\Services\MockPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoyaltyService(new MockPaymentService());
    }

    /** @test */
    public function it_returns_correct_summary_structure(): void
    {
        $user = User::factory()->create();

        Achievement::create([
            'name'                     => 'First Purchase',
            'description'              => '',
            'required_purchase_count'  => 1,
            'required_purchase_amount' => 0,
        ]);

        Badge::create([
            'name'                        => 'Beginner',
            'description'                 => '',
            'required_achievements_count' => 1,
            'icon'                        => '🥉',
        ]);

        $summary = $this->service->getUserAchievementSummary($user);

        $this->assertArrayHasKey('unlocked_achievements', $summary);
        $this->assertArrayHasKey('next_available_achievements', $summary);
        $this->assertArrayHasKey('current_badge', $summary);
        $this->assertArrayHasKey('next_badge', $summary);
        $this->assertArrayHasKey('remaining_to_unlock_next_badge', $summary);
    }

    /** @test */
    public function mock_payment_service_returns_expected_structure(): void
    {
        $user    = User::factory()->create();
        $payment = new MockPaymentService();
        $result  = $payment->processCashback($user, 'Test');

        $this->assertSame('success', $result['status']);
        $this->assertSame(300, $result['amount']);
        $this->assertSame('NGN', $result['currency']);
        $this->assertStringStartsWith('CASHBACK-', $result['reference']);
    }
}
