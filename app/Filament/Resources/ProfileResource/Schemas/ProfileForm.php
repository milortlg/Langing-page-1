<?php

namespace App\Filament\Resources\ProfileResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\Profile;
use Illuminate\Support\Str;

class ProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                                // À la création, propose automatiquement le lien à partir du nom
                                if ($operation === 'create' && blank($get('slug'))) {
                                    $set('slug', Profile::uniqueSlugFrom($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->label('Lien public')
                            ->prefix(fn () => Str::finish(preg_replace('#^https?://#', '', config('app.url')), '/'))
                            ->helperText('Le lien à mettre dans tes pubs. Lettres minuscules, chiffres et tirets uniquement.')
                            ->required()
                            ->maxLength(100)
                            ->regex('/^[a-z0-9-]+$/')
                            ->notIn(Profile::RESERVED_SLUGS)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'regex' => 'Utilise uniquement des lettres minuscules, des chiffres et des tirets (ex : alycia).',
                                'not_in' => 'Ce lien est réservé par le site, choisis-en un autre.',
                                'unique' => 'Ce lien est déjà utilisé par un autre profil.',
                            ])
                            ->dehydrateStateUsing(fn (?string $state) => Str::slug((string) $state)),
                        Forms\Components\Textarea::make('biography')
                            ->placeholder('Je suis une femme qui aime les hommes qui osent. 💋')
                            ->label('Biographie')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->placeholder('J\'aime ceux qui osent. 💋')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_online')
                            ->label('En ligne')
                            ->default(false),
                        Forms\Components\TextInput::make('action_label')
                            ->label('Label du bouton d\'action')
                            ->placeholder("S'abonner au VIP d'alycia")
                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('rencontre_primary_label')
                            ->label('Label bouton rencontre (principal)')
                            ->placeholder("Accepter l'invitation")
                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('rencontre_secondary_label')
                            ->label('Label bouton rencontre (secondaire)')
                            ->placeholder('Découvrir le profil')
                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('script_url')
                            ->label('URL du script')
                            ->placeholder('https://c.op4pro.com/8/js/script.js')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Médias')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('banner_media')
                        ->disk('public')
                        ->collection('banner')
                        ->conversion('banner_preview')
                        ->image()
                        ->imageEditor(),
                    
                    SpatieMediaLibraryFileUpload::make('avatar_media')
                        ->disk('public')
                        ->collection('avatar')
                        ->conversion('avatar_preview')
                        ->image()
                        ->imageEditor()
                        ->avatar(),

                    SpatieMediaLibraryFileUpload::make('logo_media')
                        ->disk('public')
                        ->collection('logo')
                        ->conversion('logo_preview')
                        ->image()
                        ->imageEditor(false),

                    SpatieMediaLibraryFileUpload::make('certification_media')
                        ->disk('public')
                        ->collection('certification')
                        ->conversion('certification_preview')
                        ->image()
                        ->imageEditor(),
                    ]),

                Section::make('Indicateurs')
                    ->description('Ces valeurs sont gérées manuellement depuis l\'admin')
                    ->schema([
                        Forms\Components\TextInput::make('photos_count')
                            ->label('Nombre de photos')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\TextInput::make('videos_count')
                            ->label('Nombre de vidéos')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\TextInput::make('likes_count')
                            ->label('Nombre de likes')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Horaires d\'activité')
                    ->schema([
                        TimePicker::make('online_from')
                        ->label('Heure début')
                        ->seconds(false)
                        ->nullable()
                        ->rule(function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($value && ! $get('online_to')) {
                                    $fail('L’heure de fin est obligatoire.');
                                }
                            };
                        }),
                        TimePicker::make('online_to')
                        ->label('Heure fin')
                        ->seconds(false)
                        ->nullable()
                        ->rule(function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($value && ! $get('online_from')) {
                                    $fail('L’heure de début est obligatoire.');
                                }
                            };
                        }),
                    ])
                    ->columns(2),
            ]);
    }
}

