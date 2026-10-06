<?php

namespace App\Filament\Resources\PostResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Models\Profile;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            /**
             * CONTENU PRINCIPAL
             */
            Section::make('Contenu')
                ->description('Texte et type de publication')
                ->schema([
                    Forms\Components\Select::make('profile_id')
                        ->label('Profil')
                        ->relationship('profile', 'name')
                        ->required()
                        ->preload()
                        ->searchable()
                        // Pré-sélectionne le profil filtré dans la liste, sinon le premier profil
                        ->default(fn () => request()->integer('profile') ?: Profile::orderBy('id')->value('id'))
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('content')
                        ->label('Contenu')
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('type')
                        ->label('Type de publication')
                        ->options([
                            'photo' => 'Photo',
                            'video' => 'Vidéo',
                            'live'  => 'Live',
                        ])
                        ->required()
                        ->default('photo'),

                    Forms\Components\TextInput::make('duration')
                        ->label('Durée')
                        ->helperText('Format conseillé : mm:ss')
                        ->maxLength(20),
                ])
                ->columns(2),

            /**
             * MÉDIAS
             */
            Section::make('Médias')
                ->description('Images ou vidéos associées au post')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('media')
                        ->label('Médias')
                        ->collection('media')
                        ->multiple()
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),

            /**
             * VISIBILITÉ & COMPORTEMENT
             */
            Section::make('Visibilité')
                ->description('Contrôle de l’affichage côté public')
                ->schema([
                    Forms\Components\Toggle::make('is_visible')
                        ->label('Visible')
                        ->default(true),

                    Forms\Components\Toggle::make('is_blurred')
                        ->label('Image floutée')
                        ->helperText('Affiche une version floutée avec cadenas en front')
                        ->default(true),

                    Forms\Components\Toggle::make('is_live')
                        ->label('En direct')
                        ->helperText('Affiche le badge LIVE')
                        ->default(false),
                ])
                ->columns(3),

            /**
             * STATISTIQUES & ORDRE
             */
            Section::make('Organisation & statistiques')
                ->description('Données internes')
                ->schema([
                    Forms\Components\TextInput::make('likes_count')
                        ->label('Nombre de likes')
                        ->numeric()
                        ->default(0),

                    Forms\Components\TextInput::make('order')
                        ->label('Ordre d’affichage')
                        ->numeric()
                        ->default(0)
                        ->required(),
                ])
                ->columns(2),

            /**
             * MÉTADONNÉES
             */
            Section::make('Métadonnées')
            ->schema([
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Date')
                    ->seconds(false)
                    // Si création : maintenant. Si édition : la valeur du modèle est déjà là.
                    ->default(fn ($record) => $record?->created_at ?? now())
                    // On veut que ce soit sauvegardé
                    ->dehydrated(true)
                    ->required(),
            ])
            ->collapsed(),
        

        ]);
    }
}
