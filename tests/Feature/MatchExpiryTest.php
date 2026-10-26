<?php

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Enums\SupportMode;
use App\Models\Category;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\VolunteerMatch;
use App\Models\VolunteerProfile;
use App\Notifications\MatchExpired;
use App\Services\MatchExpiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function staleMatch(MatchStatus $status = MatchStatus::Pending, bool $stale = true): VolunteerMatch
{
    $student = User::factory()->create();
    $supportRequest = SupportRequest::create([
        'user_id' => $student->id,
        'category_id' => Category::factory()->create()->id,
        'description' => 'I need help understanding SQL joins.',
        'support_mode' => SupportMode::F2F,
        'status' => RequestStatus::Pending,
    ]);

    return VolunteerMatch::create([
        'support_request_id' => $supportRequest->id,
        'volunteer_profile_id' => VolunteerProfile::factory()->create()->id,
        'status' => $status,
        'expires_at' => $stale ? now()->subHour() : now()->addHours(48),
    ]);
}

it('expires a pending match past its window and re-opens the request', function () {
    Notification::fake();

    $match = staleMatch();

    $expired = app(MatchExpiryService::class)->expireStale();

    expect($expired)->toBe(1)
        ->and($match->refresh()->status)->toBe(MatchStatus::Expired)
        ->and($match->supportRequest->refresh()->status)->toBe(RequestStatus::Open);

    Notification::assertSentTo($match->supportRequest->user, MatchExpired::class);
});

it('leaves matches still inside their window alone', function () {
    Notification::fake();

    $match = staleMatch(stale: false);

    $expired = app(MatchExpiryService::class)->expireStale();

    expect($expired)->toBe(0)
        ->and($match->refresh()->status)->toBe(MatchStatus::Pending)
        ->and($match->supportRequest->refresh()->status)->toBe(RequestStatus::Pending);

    Notification::assertNothingSent();
});

it('leaves already answered matches alone', function () {
    $match = staleMatch(MatchStatus::Rejected);

    expect(app(MatchExpiryService::class)->expireStale())->toBe(0)
        ->and($match->refresh()->status)->toBe(MatchStatus::Rejected);
});

it('runs through the matches:expire artisan command', function () {
    $match = staleMatch();

    $this->artisan('matches:expire')->assertSuccessful();

    expect($match->refresh()->status)->toBe(MatchStatus::Expired);
});

it('expires stale matches lazily when the student opens the requests page', function () {
    $match = staleMatch();

    $this->actingAs($match->supportRequest->user)
        ->get(route('support-requests.index'))
        ->assertOk();

    expect($match->refresh()->status)->toBe(MatchStatus::Expired);
});

it('lets the student pick another volunteer after expiry', function () {
    Notification::fake();

    $match = staleMatch();
    app(MatchExpiryService::class)->expireStale();

    $anotherVolunteer = VolunteerProfile::factory()->create();

    $this->actingAs($match->supportRequest->user)
        ->post(route('volunteer-matches.store', $anotherVolunteer), ['support_request' => $match->support_request_id])
        ->assertRedirect(route('support-requests.index'));

    expect($match->supportRequest->refresh()->status)->toBe(RequestStatus::Pending);
});
