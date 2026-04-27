<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\Reporting\Enums\ReportReason;
use App\Reporting\Enums\ReportStatus;
use App\Reporting\Models\ReportModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    RateLimiter::clear('ghost:127.0.0.1');
});

it('stores a report for an AI message', function () {
    /** @var TestCase $this */
    $reporter = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();
    $chat = ChatModel::factory()->create(['user_id' => $reporter->id, 'character_id' => $character->id]);
    $message = MessageModel::create([
        'chat_id' => $chat->id,
        'sender_role' => SenderRole::Character,
        'character_id' => $character->id,
        'content' => 'sketchy AI reply',
    ]);

    RateLimiter::clear('report:'.$reporter->id);

    $response = $this->actingAs($reporter)->post(route('report.store'), [
        'reportable_type' => 'message',
        'reportable_id' => $message->id,
        'reason' => ReportReason::Nsfw->value,
        'description' => 'pisała o seksie',
    ]);

    $response->assertCreated();

    $report = ReportModel::query()->first();
    expect($report->reporter_id)->toBe($reporter->id);
    expect($report->reason)->toBe(ReportReason::Nsfw);
    expect($report->status)->toBe(ReportStatus::Pending);
    expect($report->reportable_type)->toBe('message');
    expect($report->reportable_id)->toBe((string) $message->id);
});

it('stores a report for a character', function () {
    /** @var TestCase $this */
    $reporter = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();

    RateLimiter::clear('report:'.$reporter->id);

    $this->actingAs($reporter)->post(route('report.store'), [
        'reportable_type' => 'character',
        'reportable_id' => $character->id,
        'reason' => ReportReason::Impersonation->value,
    ])->assertCreated();

    expect(ReportModel::query()->where('reportable_type', 'character')->count())->toBe(1);
});

it('rejects unknown reportable type', function () {
    /** @var TestCase $this */
    $reporter = UserModel::factory()->create();

    $this->actingAs($reporter)->post(route('report.store'), [
        'reportable_type' => 'user',
        'reportable_id' => '1',
        'reason' => ReportReason::Other->value,
    ])->assertSessionHasErrors('reportable_type');
});

it('returns 404 when reported message does not exist', function () {
    /** @var TestCase $this */
    $reporter = UserModel::factory()->create();
    RateLimiter::clear('report:'.$reporter->id);

    $this->actingAs($reporter)->post(route('report.store'), [
        'reportable_type' => 'message',
        'reportable_id' => '999999',
        'reason' => ReportReason::Other->value,
    ])->assertNotFound();
});

it('creates ghost reporter when anonymous user reports', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create();

    $response = $this->post(route('report.store'), [
        'reportable_type' => 'character',
        'reportable_id' => $character->id,
        'reason' => ReportReason::Nsfw->value,
    ]);

    $response->assertCreated();
    expect(UserModel::query()->guests()->count())->toBe(1);

    $report = ReportModel::query()->first();
    $ghost = UserModel::query()->guests()->first();
    expect($report->reporter_id)->toBe($ghost->id);
});

it('throttles too many reports from same user', function () {
    /** @var TestCase $this */
    $reporter = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();
    RateLimiter::clear('report:'.$reporter->id);

    foreach (range(1, 5) as $_) {
        $this->actingAs($reporter)->post(route('report.store'), [
            'reportable_type' => 'character',
            'reportable_id' => $character->id,
            'reason' => ReportReason::Other->value,
        ])->assertCreated();
    }

    $this->actingAs($reporter)->post(route('report.store'), [
        'reportable_type' => 'character',
        'reportable_id' => $character->id,
        'reason' => ReportReason::Other->value,
    ])->assertStatus(429);
});
