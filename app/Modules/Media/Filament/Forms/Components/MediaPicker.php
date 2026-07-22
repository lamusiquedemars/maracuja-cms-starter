<?php

namespace App\Modules\Media\Filament\Forms\Components;

use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Filament\Tables\MediaPickerTable;
use App\Modules\Media\Models\MediaAsset;
use Filament\Actions\Action;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Support\Enums\Width;

class MediaPicker extends ModalTableSelect
{
    private ?MediaType $acceptedMediaType = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Média')
            ->tableConfiguration(MediaPickerTable::class)
            ->tableArguments(fn (): array => $this->tableArgumentsForMedia())
            ->getOptionLabelFromRecordUsing(fn (MediaAsset $record): string => $record->display_name)
            ->selectAction(fn (Action $action): Action => $action
                ->label(fn (): string => match ($this->acceptedMediaType) {
                    MediaType::Image => 'Choisir ou importer une image',
                    MediaType::Document => 'Choisir ou importer un document',
                    null => 'Choisir ou importer un média',
                })
                ->modalHeading($this->selectionModalHeading())
                ->modalSubmitActionLabel('Utiliser ce média')
                ->modalWidth(Width::SevenExtraLarge));
    }

    public function imagesOnly(): static
    {
        $this->acceptedMediaType = MediaType::Image;

        return $this;
    }

    public function documentsOnly(): static
    {
        $this->acceptedMediaType = MediaType::Document;

        return $this;
    }

    public function acceptedMediaType(): ?MediaType
    {
        return $this->acceptedMediaType;
    }

    /** @return array{type: string|null} */
    public function tableArgumentsForMedia(): array
    {
        return ['type' => $this->acceptedMediaType?->value];
    }

    public function selectionModalHeading(): string
    {
        return match ($this->acceptedMediaType) {
            MediaType::Image => 'Choisir une image',
            MediaType::Document => 'Choisir un document',
            null => 'Choisir un média',
        };
    }
}
