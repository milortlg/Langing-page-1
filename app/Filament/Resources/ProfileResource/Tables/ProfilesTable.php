<?php

namespace App\Filament\Resources\ProfileResource\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use App\Models\Profile;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->collection('avatar')
                    ->label('Avatar')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Lien')
                    ->formatStateUsing(fn (Profile $record) => preg_replace('#^https?://#', '', $record->public_url))
                    ->copyable()
                    ->copyableState(fn (Profile $record) => $record->public_url)
                    ->copyMessage('Lien copié !')
                    ->icon('heroicon-o-link')
                    ->searchable(),
                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Publications')
                    ->counts('posts')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_online')
                    ->label('En ligne')
                    ->boolean(),
                Tables\Columns\TextColumn::make('photos_count')
                    ->label('Photos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('videos_count')
                    ->label('Vidéos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('likes_count')
                    ->label('Likes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Voir la page')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Profile $record) => $record->public_url)
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->label('Modifier'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

