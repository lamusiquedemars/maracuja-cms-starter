<?php

namespace App\Modules\Media\Filament\Forms\Components;

use App\Models\User;
use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Services\MediaStorageService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaIdSelect extends Select
{
    private MediaType $acceptedMediaType = MediaType::Image;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->options(fn (): array => MediaAsset::query()
                ->where('type', $this->acceptedMediaType)
                ->orderBy('display_name')
                ->get()
                ->mapWithKeys(fn (MediaAsset $media): array => [
                    $media->getKey() => $this->optionLabel($media),
                ])
                ->all())
            ->searchable()
            ->preload()
            ->allowHtml()
            ->createOptionAction(fn (Action $action): Action => $action
                ->label('Importer depuis l’ordinateur')
                ->modalHeading(fn (): string => $this->acceptedMediaType === MediaType::Image
                    ? 'Importer une image'
                    : 'Importer un document'))
            ->createOptionForm(fn (): array => [
                FileUpload::make('file')
                    ->label('Fichier')
                    ->storeFiles(false)
                    ->acceptedFileTypes($this->acceptedFileTypes())
                    ->maxSize($this->maximumSize())
                    ->required(),
            ])
            ->createOptionUsing(function (array $data, MediaStorageService $storage): int {
                $file = $data['file'] ?? null;

                abort_unless($file instanceof TemporaryUploadedFile, 422, 'Le fichier importé est invalide.');

                /** @var User|null $uploader */
                $uploader = auth()->user();

                return $storage->store($file, uploader: $uploader)->getKey();
            });
    }

    public function optionLabel(MediaAsset $media): string
    {
        $name = e($media->display_name ?: $media->original_name);
        $details = collect([
            $media->original_name !== $media->display_name ? $media->original_name : null,
            $media->dimensionsLabel(),
            $media->formattedSize(),
        ])->filter()->map(e(...))->implode(' · ');

        $preview = $media->isImage()
            ? sprintf(
                '<img src="%s" alt="" loading="lazy" style="width:48px;height:48px;object-fit:cover;border-radius:6px;background:#f3f4f6;flex:none">',
                e($media->publicPath()),
            )
            : '<span aria-hidden="true" style="display:flex;width:48px;height:48px;align-items:center;justify-content:center;border-radius:6px;background:#fef2f2;color:#b91c1c;font-size:11px;font-weight:700;flex:none">PDF</span>';

        return sprintf(
            '<span style="display:flex;align-items:center;gap:12px;min-width:0">%s<span style="display:flex;min-width:0;flex-direction:column;text-align:left"><strong style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">%s</strong><small style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#6b7280">%s</small></span></span>',
            $preview,
            $name,
            $details,
        );
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

    private function acceptedFileTypes(): array
    {
        return $this->acceptedMediaType === MediaType::Image
            ? ['image/jpeg', 'image/png', 'image/webp']
            : ['application/pdf'];
    }

    private function maximumSize(): int
    {
        return (int) config($this->acceptedMediaType === MediaType::Image
            ? 'maracuja.media.image_max_size_kb'
            : 'maracuja.media.document_max_size_kb');
    }
}
