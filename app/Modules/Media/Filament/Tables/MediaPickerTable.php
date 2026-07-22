<?php

namespace App\Modules\Media\Filament\Tables;

use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Filament\Actions\MediaUploadAction;
use App\Modules\Media\Models\MediaAsset;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MediaPickerTable
{
    public static function configure(Table $table): Table
    {
        $type = filled($table->getArguments()['type'] ?? null)
            ? MediaType::tryFrom((string) $table->getArguments()['type'])
            : null;

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->when($type, fn (Builder $query, MediaType $type): Builder => $query->where('type', $type)))
            ->columns([
                ViewColumn::make('preview')
                    ->label('Aperçu')
                    ->view('filament.media.columns.preview'),
                Stack::make([
                    TextColumn::make('display_name')
                        ->label('Nom')
                        ->weight('semibold')
                        ->searchable(['display_name', 'original_name', 'alt_text', 'caption', 'credit'])
                        ->wrap(),
                    TextColumn::make('original_name')
                        ->label('Fichier')
                        ->color('gray')
                        ->size('sm')
                        ->limit(40),
                    TextColumn::make('details')
                        ->state(fn (MediaAsset $record): string => collect([
                            $record->dimensionsLabel(),
                            $record->formattedSize(),
                        ])->filter()->implode(' · '))
                        ->color('gray')
                        ->size('sm'),
                ])->space(2),
            ])
            ->contentGrid([
                'sm' => 2,
                'lg' => 3,
            ])
            ->defaultSort('created_at', 'desc')
            ->filters($type ? [] : [
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        MediaType::Image->value => 'Images',
                        MediaType::Document->value => 'Documents',
                    ]),
            ])
            ->headerActions([
                MediaUploadAction::make($type),
            ])
            ->emptyStateHeading('Aucun média disponible')
            ->emptyStateDescription('Ajoutez un média ou modifiez vos filtres.');
    }
}
