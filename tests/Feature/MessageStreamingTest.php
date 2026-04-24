<?php

declare(strict_types=1);

use App\Messaging\SenderRole;
use App\Models\Character;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\AnonymousAgent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Character, 2: Chat}
 */
function seedChat(): array
{
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id]);
    $chat = Chat::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    return [$user, $character, $chat];
}

it('store creates user + empty character message and returns HTML', function () {
    /** @var TestCase $this */
    [$user, $character, $chat] = seedChat();

    $response = $this->actingAs($user)
        ->post("/chat/{$chat->id}/messages", ['content' => 'Cześć!']);

    $response->assertCreated();
    $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    $response->assertHeader('X-Character-Message-Id');

    expect($chat->messages()->count())->toBe(2);

    $userMsg = $chat->messages()->where('sender_role', SenderRole::User->value)->first();
    $charMsg = $chat->messages()->where('sender_role', SenderRole::Character->value)->first();

    expect($userMsg->content)->toBe('Cześć!');
    expect($userMsg->user_id)->toBe($user->id);
    expect($charMsg->content)->toBe('');
    expect($charMsg->character_id)->toBe($character->id);

    $response->assertSeeText('Cześć!');
    $response->assertSee('data-streaming="true"', false);
});

it('store validates content required', function () {
    /** @var TestCase $this */
    [$user, , $chat] = seedChat();

    $this->actingAs($user)
        ->post("/chat/{$chat->id}/messages", ['content' => ''])
        ->assertSessionHasErrors('content');
});

it('store validates content max length', function () {
    /** @var TestCase $this */
    [$user, , $chat] = seedChat();

    $this->actingAs($user)
        ->post("/chat/{$chat->id}/messages", ['content' => str_repeat('x', 8001)])
        ->assertSessionHasErrors('content');
});

it('store 404s when chat belongs to another user', function () {
    /** @var TestCase $this */
    [, , $chat] = seedChat();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->post("/chat/{$chat->id}/messages", ['content' => 'hej'])
        ->assertNotFound();
});

it('store requires auth', function () {
    /** @var TestCase $this */
    [, , $chat] = seedChat();

    $this->post("/chat/{$chat->id}/messages", ['content' => 'hej'])
        ->assertRedirect('/login');
});

it('stream updates character message content from faked AI response', function () {
    /** @var TestCase $this */
    AnonymousAgent::fake(['Hej, miło Cię poznać!']);

    [$user, , $chat] = seedChat();

    $this->actingAs($user)->post("/chat/{$chat->id}/messages", ['content' => 'Cześć!']);

    $response = $this->actingAs($user)->get("/chat/{$chat->id}/messages/stream");

    expect($response->baseResponse)->toBeInstanceOf(StreamedResponse::class);
    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('text/event-stream');
    $response->assertHeader('X-Accel-Buffering', 'no');

    // Drain the stream — closure runs, character Message gets updated with fake AI text.
    // We don't capture the SSE body here because ob_flush() in the controller bypasses
    // the test's output buffer; the important invariant is that the character message
    // gets its content persisted after the stream completes.
    @$response->baseResponse->sendContent();

    $charMsg = Message::where('chat_id', $chat->id)
        ->where('sender_role', SenderRole::Character->value)
        ->first();

    expect($charMsg->content)->toBe('Hej, miło Cię poznać!');
});

it('stream 404s when chat belongs to another user', function () {
    /** @var TestCase $this */
    [, , $chat] = seedChat();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get("/chat/{$chat->id}/messages/stream")
        ->assertNotFound();
});

it('stream 404s when no pending character message exists', function () {
    /** @var TestCase $this */
    [$user, , $chat] = seedChat();

    $this->actingAs($user)
        ->get("/chat/{$chat->id}/messages/stream")
        ->assertNotFound();
});
