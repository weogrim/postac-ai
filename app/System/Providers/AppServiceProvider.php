<?php

declare(strict_types=1);

namespace App\System\Providers;

use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageLimitModel;
use App\Chat\Models\MessageModel;
use App\Legal\Models\ConsentModel;
use App\Legal\Models\LegalDocumentModel;
use App\Moderation\Contracts\ModerationProvider;
use App\Moderation\Models\SafetyEventModel;
use App\Moderation\Providers\NoOpProvider;
use App\Moderation\Providers\OpenAiModerationProvider;
use App\Reporting\Models\ReportModel;
use App\User\Models\UserModel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Image;
use Laravel\Cashier\Cashier;
use Plank\Mediable\Facades\ImageManipulator;
use Plank\Mediable\ImageManipulation;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Cashier::ignoreRoutes();
        Cashier::useCustomerModel(UserModel::class);

        $this->app->bind(ModerationProvider::class, function (Application $app): ModerationProvider {
            $name = (string) config('moderation.default', 'noop');
            $config = (array) config('moderation.providers.'.$name, []);
            $driver = $config['driver'] ?? $name;

            return match ($driver) {
                'openai' => new OpenAiModerationProvider(
                    apiKey: (string) ($config['key'] ?? ''),
                    baseUrl: (string) ($config['url'] ?? 'https://api.openai.com/v1'),
                    model: (string) ($config['model'] ?? 'omni-moderation-latest'),
                    timeoutSeconds: (float) ($config['timeout'] ?? 3.0),
                ),
                default => new NoOpProvider,
            };
        });
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'user' => UserModel::class,
            'character' => CharacterModel::class,
            'chat' => ChatModel::class,
            'message' => MessageModel::class,
            'message_limit' => MessageLimitModel::class,
            'legal_document' => LegalDocumentModel::class,
            'consent' => ConsentModel::class,
            'safety_event' => SafetyEventModel::class,
            'report' => ReportModel::class,
        ]);

        Factory::guessFactoryNamesUsing(static function (string $modelName): string {
            $base = preg_replace('/Model$/', '', class_basename($modelName));

            return 'Database\\Factories\\'.$base.'Factory';
        });

        Gate::before(fn (UserModel $user): ?bool => $user->hasRole('super_admin') ? true : null);

        ImageManipulator::defineVariant(
            'square',
            ImageManipulation::make(function (Image $image): void {
                $image->cover(512, 512);
            })->outputWebpFormat()->setOutputQuality(85),
        );

        ImageManipulator::defineVariant(
            'thumb',
            ImageManipulation::make(function (Image $image): void {
                $image->cover(96, 96);
            })->outputWebpFormat()->setOutputQuality(80),
        );
    }
}
