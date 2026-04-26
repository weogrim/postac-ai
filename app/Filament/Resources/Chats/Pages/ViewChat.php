<?php

declare(strict_types=1);

namespace App\Filament\Resources\Chats\Pages;

use App\Chat\Models\ChatModel;
use App\Filament\Resources\Chats\ChatResource;
use Filament\Resources\Pages\Page;

class ViewChat extends Page
{
    protected static string $resource = ChatResource::class;

    protected string $view = 'filament.resources.chats.pages.view-chat';

    public ChatModel $record;

    public function mount(string $record): void
    {
        $this->record = ChatModel::query()->withTrashed()->findOrFail($record);
        $this->record->loadMissing(['user', 'character', 'messages' => fn ($q) => $q->orderBy('created_at')]);
    }

    public function getTitle(): string
    {
        return "Czat {$this->record->user->email} ↔ {$this->record->character->name}";
    }
}
