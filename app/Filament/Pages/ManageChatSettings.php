<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Chat\Enums\ModelType;
use App\Chat\Settings\ChatSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property Schema $form
 */
class ManageChatSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Ustawienia czatu';

    protected static string|UnitEnum|null $navigationGroup = 'Konfiguracja';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.manage-chat-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $settings = app(ChatSettings::class);

        $this->form->fill([
            'defaultModel' => $settings->defaultModel->value,
            'historyLength' => $settings->historyLength,
            'beforeUserMessage' => $settings->beforeUserMessage,
            'afterUserMessage' => $settings->afterUserMessage,
            'temperature' => $settings->temperature,
            'maxTokens' => $settings->maxTokens,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('defaultModel')
                    ->label('Domyślny model')
                    ->options(collect(ModelType::cases())->mapWithKeys(fn (ModelType $m): array => [$m->value => $m->value])->all())
                    ->required(),

                TextInput::make('historyLength')
                    ->label('Długość historii (liczba wiadomości)')
                    ->integer()
                    ->minValue(0)
                    ->required(),

                TextInput::make('temperature')
                    ->label('Temperatura')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(2)
                    ->step(0.1)
                    ->required(),

                TextInput::make('maxTokens')
                    ->label('Max tokenów')
                    ->integer()
                    ->minValue(1)
                    ->required(),

                Textarea::make('beforeUserMessage')
                    ->label('Wrapper przed wiadomością użytkownika')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('afterUserMessage')
                    ->label('Wrapper po wiadomości użytkownika')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = app(ChatSettings::class);
        $settings->defaultModel = ModelType::from($data['defaultModel']);
        $settings->historyLength = (int) $data['historyLength'];
        $settings->temperature = (float) $data['temperature'];
        $settings->maxTokens = (int) $data['maxTokens'];
        $settings->beforeUserMessage = (string) $data['beforeUserMessage'];
        $settings->afterUserMessage = (string) $data['afterUserMessage'];
        $settings->save();

        Notification::make()
            ->title('Ustawienia zapisane')
            ->success()
            ->send();
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Zapisz')
                ->submit('save'),
        ];
    }
}
