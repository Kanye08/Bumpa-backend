# Bumpa Loyalty — Backend API

A Laravel 11 REST API powering the Bumpa Loyalty Program. Handles user authentication, purchase recording, achievement unlocking, badge earning, and cashback payments.

---

## Tech Stack

- **PHP 8.2+**
- **Laravel 11**
- **Laravel Fortify** — authentication actions (register, login, password updates)
- **Laravel Sanctum** — stateless API token authentication
- **SQLite** (default) or MySQL / PostgreSQL
- **PHPUnit** — feature and unit tests

---

## Requirements

- PHP 8.2 or higher
- Composer
- SQLite (built into PHP, zero setup) **or** MySQL 8+ / PostgreSQL 14+

---

## Installation

```
# 1. Install PHP dependencies
composer install

# 2. Create your environment file
cp .env.example .env

# 3. Generate the application key
php artisan key:generate

# 4. Run database migrations
php artisan migrate

# 5. Seed achievements, badges, and a demo user
php artisan db:seed

# 6. Start the development server
php artisan serve
```
The API will be available at **http://localhost:8000**.


## Environment Variables

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bumpa_loyalty
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then run `php artisan migrate --seed`.


## API Reference

All protected routes require the header:
Authorization: Bearer <token>
Tokens are returned from `/api/login` and `/api/register`.

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/register` | No | Register a new user |
| `POST` | `/api/login` | No | Login and receive an API token |
| `POST` | `/api/logout` | Yes | Revoke the current token |
| `GET` | `/api/me` | Yes | Get the authenticated user's profile |

### Loyalty

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/users/{id}/achievements` | Yes | Get full loyalty summary for a user |
| `POST` | `/api/purchases` | Yes | Record a purchase and trigger achievement checks |
| `GET` | `/api/purchases` | Yes | List all purchases for the authenticated user |

---

## Request & Response Examples

### Register

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Ada Okafor","email":"ada@example.com","password":"password","password_confirmation":"password"}'
```

```json
{
  "message": "Registration successful.",
  "user": { "id": 1, "name": "Ada Okafor", "email": "ada@example.com" },
  "token": "1|abc123..."
}
```

### Record a Purchase

```bash
curl -X POST http://localhost:8000/api/purchases \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 10000}'
```

```json
{
  "message": "Purchase recorded successfully.",
  "purchase": { "id": 1, "amount": "10000.00", "reference": "REF-ABC123" },
  "summary": {
    "unlocked_achievements": ["First Purchase", "Loyal Buyer"],
    "next_available_achievements": ["Shopper", "Regular Customer", "Big Spender", "VIP", "Champion", "Elite Member"],
    "current_badge": "Beginner",
    "next_badge": "Bronze",
    "remaining_to_unlock_next_badge": 2
  }
}
```

### Get Achievement Summary

```bash
curl http://localhost:8000/api/users/1/achievements \
  -H "Authorization: Bearer YOUR_TOKEN"
```

```json
{
  "unlocked_achievements": ["First Purchase", "Loyal Buyer"],
  "next_available_achievements": ["Shopper", "Regular Customer", "Big Spender", "VIP", "Champion", "Elite Member"],
  "current_badge": "Beginner",
  "next_badge": "Bronze",
  "remaining_to_unlock_next_badge": 2
}

---

## Achievements & Badges

### Achievements (8 total)

| Achievement | Required Purchases | Required Spend (₦) |
|-------------|-------------------|---------------------|
| First Purchase | 1 | — |
| Shopper | 5 | — |
| Regular Customer | 10 | — |
| Loyal Buyer | — | 10,000 |
| Big Spender | — | 50,000 |
| VIP | 25 | — |
| Champion | — | 100,000 |
| Elite Member | 50 | — |

### Badges (4 tiers)

| Badge | Achievements Required | Cashback Triggered |
|-------|-----------------------|--------------------|
| 🥉 Beginner | 2 | ₦300 |
| 🥈 Bronze | 4 | ₦300 |
| 🥇 Silver | 6 | ₦300 |
| 🏆 Gold | 8 | ₦300 |

---

## Architecture

### Event Flow

When a purchase is recorded, the following pipeline runs:

POST /api/purchases
  └── PurchaseController
        └── LoyaltyService::processUserPurchase()
              ├── checkAndUnlockAchievements()
              │     └── foreach eligible achievement:
              │           attach to user → fire AchievementUnlocked event
              └── checkAndUnlockBadges()
                    └── foreach eligible badge:
                          attach to user → fire BadgeUnlocked event
                                └── SendCashbackOnBadgeUnlocked listener
                                      └── MockPaymentService::processCashback()
                                            └── logs ₦300 cashback to laravel.log


### Key Classes

| Class | Location | Responsibility |
|-------|----------|----------------|
| `LoyaltyService` | `app/Services/` | Core achievement/badge logic |
| `MockPaymentService` | `app/Services/` | Simulates ₦300 cashback  |
| `AchievementUnlocked` | `app/Events/` | Fired when a user unlocks an achievement |
| `BadgeUnlocked` | `app/Events/` | Fired when a user earns a badge |
| `SendCashbackOnBadgeUnlocked` | `app/Listeners/` | Listens for `BadgeUnlocked`, calls payment service |
| `AchievementController` | `app/Http/Controllers/` | Serves the `GET /achievements` endpoint |
| `PurchaseController` | `app/Http/Controllers/` | Records purchases, returns updated summary |
| `AuthController` | `app/Http/Controllers/` | Token-based register / login / logout |

---

## Running Tests

```bash
# Run all tests
php artisan test

# Run only feature tests
php artisan test --testsuite=Feature

# Run only unit tests
php artisan test --testsuite=Unit

# With coverage (requires Xdebug)
php artisan test --coverage
```

### Test Coverage

| Test | What it verifies |
|------|-----------------|
| `it_returns_achievement_summary_for_a_user` | API returns correct JSON structure |
| `it_unlocks_first_purchase_achievement_after_one_purchase` | Achievement unlocked on first purchase |
| `it_unlocks_badge_when_enough_achievements_are_earned` | Badge earned after threshold met |
| `it_fires_achievement_unlocked_event` | `AchievementUnlocked` event is dispatched |
| `it_fires_badge_unlocked_event_and_logs_cashback` | `BadgeUnlocked` event is dispatched |
| `it_requires_authentication_to_view_achievements` | Unauthenticated requests return 401 |
| `it_does_not_duplicate_unlocked_achievements` | Same achievement is never unlocked twice |

---

## Demo Credentials

Seeded by `php artisan db:seed`:

```
Email: demo@bumpa.com
Password: password
```

---

## Verifying Cashback in Logs

After a badge is earned, check:

```bash
tail -f storage/logs/laravel.log
```

You will see:

```
[INFO] [MockPayment] Cashback processed {
  "status": "success",
  "reference": "CASHBACK-ABC123XYZ",
  "amount": 300,
  "currency": "NGN",
  "user_id": 1,
  "user_email": "demo@bumpa.shop",
  "reason": "Badge unlocked: Beginner",
  "timestamp": "2026-04-17T10:00:00+00:00"
}
```

---