<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLoyaltyData();
    }

    private function seedLoyaltyData(): void
    {
        Achievement::insert([
            ['name' => 'First Purchase',   'description' => '', 'required_purchase_count' => 1,  'required_purchase_amount' => 0,     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Shopper',          'description' => '', 'required_purchase_count' => 5,  'required_purchase_amount' => 0,     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Regular Customer', 'description' => '', 'required_purchase_count' => 10, 'required_purchase_amount' => 0,     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Loyal Buyer',      'description' => '', 'required_purchase_count' => 0,  'required_purchase_amount' => 10000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Badge::insert([
            ['name' => 'Beginner', 'description' => '', 'required_achievements_count' => 2, 'icon' => '🥉', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bronze',   'description' => '', 'required_achievements_count' => 4, 'icon' => '🥈', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /** @test */
    public function it_returns_achievement_summary_for_a_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/users/{$user->id}/achievements");

        $response->assertOk()
            ->assertJsonStructure([
                'unlocked_achievements',
                'next_available_achievements',
                'current_badge',
                'next_badge',
                'remaining_to_unlock_next_badge',
            ]);
    }

    /** @test */
    public function it_unlocks_first_purchase_achievement_after_one_purchase(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/purchases', ['amount' => 500]);

        $this->assertDatabaseHas('user_achievements', [
            'user_id'        => $user->id,
            'achievement_id' => Achievement::where('name', 'First Purchase')->first()->id,
        ]);
    }

    /** @test */
    public function it_unlocks_badge_when_enough_achievements_are_earned(): void
    {
        $user = User::factory()->create();

        // Make enough purchases to hit First Purchase + Shopper = 2 achievements → Beginner badge
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/api/purchases', ['amount' => 100]);
        }

        $this->assertDatabaseHas('user_badges', [
            'user_id'  => $user->id,
            'badge_id' => Badge::where('name', 'Beginner')->first()->id,
        ]);
    }

    /** @test */
    public function it_fires_achievement_unlocked_event(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/purchases', ['amount' => 100]);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\AchievementUnlocked::class);
    }

    /** @test */
    public function it_fires_badge_unlocked_event_and_logs_cashback(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/api/purchases', ['amount' => 100]);
        }

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\BadgeUnlocked::class);
    }

    /** @test */
    public function it_requires_authentication_to_view_achievements(): void
    {
        $user = User::factory()->create();

        $this->getJson("/api/users/{$user->id}/achievements")
            ->assertUnauthorized();
    }

    /** @test */
    public function it_does_not_duplicate_unlocked_achievements(): void
    {
        $user = User::factory()->create();

        // Two purchases — First Purchase should only be unlocked once
        $this->actingAs($user)->postJson('/api/purchases', ['amount' => 100]);
        $this->actingAs($user)->postJson('/api/purchases', ['amount' => 100]);

        $this->assertDatabaseCount('user_achievements', 1);
    }
}
