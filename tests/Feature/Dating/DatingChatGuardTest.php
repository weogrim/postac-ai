<?php

declare(strict_types=1);

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\Dating\Models\DatingProfileModel;
use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\ConsentModel;
use App\Legal\Models\LegalDocumentModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

function datingCharacter(): CharacterModel
{
    $character = CharacterModel::factory()->create([
        'kind' => CharacterKind::Dating,
        'is_official' => true,
    ]);
    DatingProfileModel::factory()->create(['character_id' => $character->id]);

    return $character;
}

it('redirects anonymous visitor to login when starting dating chat', function () {
    /** @var TestCase $this */
    $character = datingCharacter();

    $this->post(route('chat.store'), ['character_id' => $character->id])
        ->assertRedirect(route('login'));

    expect(ChatModel::query()->where('character_id', $character->id)->exists())->toBeFalse();
});

it('redirects authenticated user without dating consent to onboarding', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = datingCharacter();

    LegalDocumentModel::create([
        'slug' => DocumentSlug::DatingTerms,
        'version' => 1,
        'title' => 'Regulamin Randek',
        'content' => 'Treść',
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->post(route('chat.store'), ['character_id' => $character->id])
        ->assertRedirect(route('dating.onboarding'));

    expect(ChatModel::query()->where('character_id', $character->id)->exists())->toBeFalse();
});

it('starts dating chat for authenticated user with consent', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = datingCharacter();

    $doc = LegalDocumentModel::create([
        'slug' => DocumentSlug::DatingTerms,
        'version' => 1,
        'title' => 'Regulamin Randek',
        'content' => 'Treść',
        'published_at' => now()->subDay(),
    ]);

    ConsentModel::create([
        'user_id' => $user->id,
        'legal_document_id' => $doc->id,
        'accepted_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('chat.store'), ['character_id' => $character->id]);

    $chat = ChatModel::query()->where('user_id', $user->id)->where('character_id', $character->id)->first();

    expect($chat)->not->toBeNull();
    $response->assertRedirect(route('chat.show', $chat));
});
