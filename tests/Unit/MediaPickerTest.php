<?php

namespace Tests\Unit;

use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Filament\Forms\Components\MediaPicker;
use App\Modules\Media\Filament\Tables\MediaPickerTable;
use Tests\TestCase;

class MediaPickerTest extends TestCase
{
    public function test_it_can_be_restricted_to_images(): void
    {
        $picker = MediaPicker::make('hero_media_id')->imagesOnly();

        $this->assertSame(MediaType::Image, $picker->acceptedMediaType());
        $this->assertSame(['type' => 'image'], $picker->tableArgumentsForMedia());
        $this->assertSame(MediaPickerTable::class, $picker->getTableConfiguration());
        $this->assertSame('Choisir une image', $picker->selectionModalHeading());
    }

    public function test_it_can_be_restricted_to_documents(): void
    {
        $picker = MediaPicker::make('document_media_id')->documentsOnly();

        $this->assertSame(MediaType::Document, $picker->acceptedMediaType());
        $this->assertSame(['type' => 'document'], $picker->tableArgumentsForMedia());
        $this->assertSame('Choisir un document', $picker->selectionModalHeading());
    }

    public function test_it_can_offer_all_media_types(): void
    {
        $picker = MediaPicker::make('media_id');

        $this->assertNull($picker->acceptedMediaType());
        $this->assertSame(['type' => null], $picker->tableArgumentsForMedia());
        $this->assertSame('Choisir un média', $picker->selectionModalHeading());
    }
}
