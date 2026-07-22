<?php

namespace App\Modules\Media\Filament\Actions;

use App\Models\User;
use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Services\MediaStorageService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaUploadAction
{
    public static function make(?MediaType $type = null): Action
    {
        $acceptedFileTypes = match ($type) {
            MediaType::Image => ['image/jpeg', 'image/png', 'image/webp'],
            MediaType::Document => ['application/pdf'],
            null => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        };
        $maximumSize = $type === MediaType::Image
            ? (int) config('maracuja.media.image_max_size_kb')
            : (int) config('maracuja.media.document_max_size_kb');

        return Action::make('uploadMedia')
            ->label('Importer depuis l’ordinateur')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->modalHeading('Importer depuis l’ordinateur')
            ->modalDescription(self::description($type))
            ->form([
                FileUpload::make('files')
                    ->label('Fichiers')
                    ->multiple()
                    ->storeFiles(false)
                    ->acceptedFileTypes($acceptedFileTypes)
                    ->maxSize($maximumSize)
                    ->required(),
            ])
            ->action(function (array $data, MediaStorageService $storage): void {
                $files = collect($data['files'] ?? [])
                    ->filter(fn (mixed $file): bool => $file instanceof TemporaryUploadedFile);

                /** @var User|null $uploader */
                $uploader = auth()->user();

                $files->each(fn (TemporaryUploadedFile $file) => $storage->store($file, uploader: $uploader));

                Notification::make()
                    ->title($files->count().' média'.($files->count() > 1 ? 's ajoutés' : ' ajouté'))
                    ->success()
                    ->send();
            });
    }

    private static function description(?MediaType $type): string
    {
        return match ($type) {
            MediaType::Image => 'Images JPEG, PNG ou WebP, 5 Mo maximum.',
            MediaType::Document => 'Documents PDF, 15 Mo maximum.',
            null => 'Images JPEG, PNG ou WebP (5 Mo) et documents PDF (15 Mo).',
        };
    }
}
