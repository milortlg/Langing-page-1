<?php

namespace App\Filament\Resources\PostResource\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('media')
                    ->collection('media')
                    ->label('Média')
                    ->limit(1),
                Tables\Columns\TextColumn::make('profile.name')
                    ->label('Profil')
                    ->badge()
                    ->placeholder('Aucun')
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->label('Contenu')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'photo' => 'primary',
                        'video' => 'success',
                        'live' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'photo' => 'Photo',
                        'video' => 'Vidéo',
                        'live' => 'Live',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Durée')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('likes_count')
                    ->label('Likes')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Ordre')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('profile_id')
                    ->label('Profil')
                    ->relationship('profile', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'photo' => 'Photo',
                        'video' => 'Vidéo',
                        'live' => 'Live',
                    ]),
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visible')
                    ->placeholder('Tous')
                    ->trueLabel('Visible uniquement')
                    ->falseLabel('Masqués uniquement'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifier'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ])
            ->defaultSort('order');
    }
}

