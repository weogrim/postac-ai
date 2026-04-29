<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\Dating\Models\DatingProfileModel;
use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\LegalDocumentModel;
use App\User\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Tags\Tag;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedRoles();
            $admin = $this->seedAdmin();
            $this->seedLegalDocuments();
            $this->seedCategories();
            $this->seedSampleData($admin);
            $this->seedDatingProfiles($admin);
        });
    }

    private function seedRoles(): void
    {
        Role::findOrCreate('super_admin', 'web');
    }

    private function seedAdmin(): UserModel
    {
        $email = trim((string) config('auth.admin_email')) ?: 'admin@postac.ai';

        $admin = UserModel::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'birthdate' => '1990-01-01',
            ]
        );

        if (! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        return $admin;
    }

    private function seedLegalDocuments(): void
    {
        $documents = [
            [
                'slug' => DocumentSlug::Terms,
                'title' => 'Regulamin postac.ai',
                'content' => "# Regulamin\n\nKorzystając z postac.ai akceptujesz, że:\n\n- Rozmawiasz z AI, nie z prawdziwymi ludźmi.\n- Treści mogą być nieprzewidywalne — to model językowy, nie wyrocznia.\n- Zabronione: NSFW, nękanie, mowa nienawiści, namowa do samookaleczenia.\n- Minimalny wiek: 13 lat (z wymogiem zgody rodzica do 16 r.ż.).\n\nW razie problemu zgłoś treść — admin zareaguje w 24h.",
            ],
            [
                'slug' => DocumentSlug::Privacy,
                'title' => 'Polityka prywatności',
                'content' => "# Prywatność\n\nZbieramy:\n\n- Email i nazwę (do logowania).\n- Datę urodzenia (do weryfikacji wieku).\n- Treść rozmów (do działania AI).\n- IP i user agent przy zgodach prawnych (compliance).\n\nDane usuwamy na żądanie. Tokeny i metryki to anonimowe agregaty.",
            ],
            [
                'slug' => DocumentSlug::DatingTerms,
                'title' => 'Regulamin sekcji Randki',
                'content' => "# Sekcja Randki\n\nZanim zaczniesz:\n\n- To są postacie AI, **nie** prawdziwi ludzie.\n- Rozmowy są rozrywkowe, nie terapeutyczne.\n- Zero NSFW — luźny flirt, nie sexting.\n- AI nie umówi się z Tobą, nie spotka, nie zaprosi do siebie.\n\nJeśli akceptujesz — baw się dobrze. Jeśli nie — wróć na stronę główną.",
            ],
        ];

        foreach ($documents as $data) {
            LegalDocumentModel::firstOrCreate(
                ['slug' => $data['slug'], 'version' => 1],
                [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'published_at' => now()->subDay(),
                ],
            );
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Polskie legendy', 'slug' => 'polskie-legendy'],
            ['name' => 'Historia', 'slug' => 'historia'],
            ['name' => 'Nauka', 'slug' => 'nauka'],
            ['name' => 'Pop kultura', 'slug' => 'pop-kultura'],
            ['name' => 'Filozofia', 'slug' => 'filozofia'],
        ];

        foreach ($categories as $i => $cat) {
            Tag::findOrCreate($cat['name'], 'category');
        }

        $tags = ['humor', 'mądrość', 'inspiracja', 'rozmowa'];
        foreach ($tags as $name) {
            Tag::findOrCreate($name, 'tag');
        }
    }

    private function seedSampleData(UserModel $admin): void
    {
        $users = UserModel::factory(4)->create()->push($admin);

        $official = [
            [
                'name' => 'Józef Piłsudski',
                'prompt' => 'Jesteś Marszałkiem Józefem Piłsudskim. Mówisz dobitnie, z pasją do niepodległości Polski.',
                'description' => 'Twórca niepodległej Polski. Marszałek, polityk, legenda.',
                'greeting' => 'Witam, panie. Co tam u was słychać w wolnej Polsce?',
                'category' => 'Polskie legendy',
            ],
            [
                'name' => 'Maria Skłodowska-Curie',
                'prompt' => 'Jesteś Marią Skłodowską-Curie, dwukrotną laureatką Nagrody Nobla. Mówisz z naukową precyzją i pasją.',
                'description' => 'Pierwsza kobieta z Nagrodą Nobla. Odkrywczyni polonu i radu.',
                'greeting' => 'Bonjour. Czy mamy dziś rozmawiać o nauce, czy o czymś innym?',
                'category' => 'Nauka',
            ],
            [
                'name' => 'Adam Mickiewicz',
                'prompt' => 'Jesteś Adamem Mickiewiczem, wieszczem narodowym. Mówisz poetycko, cytujesz "Pana Tadeusza".',
                'description' => 'Wieszcz narodowy, autor "Pana Tadeusza" i "Dziadów".',
                'greeting' => 'Litwo, ojczyzno moja... Witaj, drogi rozmówco.',
                'category' => 'Polskie legendy',
            ],
        ];

        $regularNames = ['Sokrates', 'Albert Einstein', 'Kleopatra', 'Leonardo da Vinci'];

        foreach ($official as $data) {
            $character = CharacterModel::factory()
                ->withAvatar()
                ->recycle($admin)
                ->create([
                    'name' => $data['name'],
                    'prompt' => $data['prompt'],
                    'description' => $data['description'],
                    'greeting' => $data['greeting'],
                    'is_official' => true,
                    'kind' => CharacterKind::Regular,
                ]);

            $tag = Tag::findFromString($data['category'], null, 'category');
            if ($tag !== null) {
                $character->attachTag($tag);
            }
        }

        foreach ($regularNames as $name) {
            CharacterModel::factory()
                ->withAvatar()
                ->recycle($users)
                ->create([
                    'name' => $name,
                    'is_official' => false,
                    'kind' => CharacterKind::Regular,
                ]);
        }

        $sampleChars = CharacterModel::query()->regular()->limit(3)->get();
        $users->take(2)->each(function (UserModel $user) use ($sampleChars): void {
            $sampleChars->random(2)->each(function (CharacterModel $character) use ($user): void {
                $chat = ChatModel::factory()->create([
                    'user_id' => $user->id,
                    'character_id' => $character->id,
                ]);
                MessageModel::factory()->fromUser($chat)->create(['content' => 'Cześć!']);
                MessageModel::factory()->fromCharacter($chat)->create(['content' => 'Witaj.']);
            });
        });
    }

    private function seedDatingProfiles(UserModel $admin): void
    {
        $profiles = [
            [
                'name' => 'Maja',
                'age' => 24,
                'city' => 'Warszawa',
                'bio' => 'Lubię kawę, koty i długie rozmowy o niczym. Chętnie pójdę na spacer po Łazienkach.',
                'interests' => ['kawa', 'koty', 'spacery'],
                'accent_color' => '#ff5d8f',
                'prompt' => 'Jesteś Mają, 24-letnią warszawianką. Flirtujesz subtelnie, lubisz humor, jesteś ciekawa rozmówcy.',
                'greeting' => 'Hej! Co tam u Ciebie? :)',
            ],
            [
                'name' => 'Kuba',
                'age' => 28,
                'city' => 'Kraków',
                'bio' => 'Programista po godzinach gram na gitarze. Szukam kogoś z poczuciem humoru.',
                'interests' => ['muzyka', 'kod', 'piwo rzemieślnicze'],
                'accent_color' => '#0ea5e9',
                'prompt' => 'Jesteś Kubą, 28-letnim programistą z Krakowa. Masz dystans, lubisz inteligentne żarty.',
                'greeting' => 'Hej, fajnie że napisałaś.',
            ],
        ];

        foreach ($profiles as $data) {
            $character = CharacterModel::factory()
                ->withAvatar()
                ->recycle($admin)
                ->create([
                    'name' => $data['name'],
                    'prompt' => $data['prompt'],
                    'greeting' => $data['greeting'],
                    'is_official' => true,
                    'kind' => CharacterKind::Dating,
                ]);

            DatingProfileModel::create([
                'character_id' => $character->id,
                'age' => $data['age'],
                'city' => $data['city'],
                'bio' => $data['bio'],
                'interests' => $data['interests'],
                'accent_color' => $data['accent_color'],
            ]);
        }
    }
}
