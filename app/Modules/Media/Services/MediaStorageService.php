<?php

namespace App\Modules\Media\Services;

use App\Models\User;
use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MediaStorageService
{
    public function __construct(
        private readonly VideoThumbnailService $videoThumbnails,
        private readonly ImageVariantService $images,
    ) {}

    /**
     * @param  array{display_name?: string|null, alt_text?: string|null, caption?: string|null, credit?: string|null}  $metadata
     */
    public function store(UploadedFile $file, array $metadata = [], ?User $uploader = null): MediaAsset
    {
        $definition = $this->definitionFor($file);
        $type = MediaType::from($definition['type']);
        $this->validateSize($file, $type);

        $disk = (string) config('maracuja.media.disk', 'public');
        $originalName = $this->cleanOriginalName($file->getClientOriginalName());

        if ($type === MediaType::Image) {
            return $this->storeImage($file, $definition, $metadata, $uploader, $originalName, $disk);
        }

        $directory = $this->directoryFor($type);
        $filename = Str::ulid().'.'.$definition['extension'];
        $path = $file->storeAs($directory, $filename, $disk);

        if (! is_string($path)) {
            throw ValidationException::withMessages([
                'file' => 'Le fichier n’a pas pu être enregistré.',
            ]);
        }

        try {
            $dimensions = $type === MediaType::Image ? @getimagesize(Storage::disk($disk)->path($path)) : false;
            $media = MediaAsset::query()->create([
                'type' => $type,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $originalName,
                'display_name' => filled($metadata['display_name'] ?? null)
                    ? trim((string) $metadata['display_name'])
                    : pathinfo($originalName, PATHINFO_FILENAME),
                'mime_type' => $definition['mime_type'],
                'extension' => $definition['extension'],
                'size' => Storage::disk($disk)->size($path),
                'width' => is_array($dimensions) ? (int) $dimensions[0] : null,
                'height' => is_array($dimensions) ? (int) $dimensions[1] : null,
                'alt_text' => $metadata['alt_text'] ?? null,
                'caption' => $metadata['caption'] ?? null,
                'credit' => $metadata['credit'] ?? null,
                'checksum' => hash_file('sha256', Storage::disk($disk)->path($path)),
                'uploaded_by' => $uploader?->getKey(),
            ]);

            if ($media->isVideo()) {
                $this->videoThumbnails->generate($media);
            }

            return $media->refresh();
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }

    /**
     * @param  array{type: string, extension: string, mime_type: string}  $definition
     * @param  array{display_name?: string|null, alt_text?: string|null, caption?: string|null, credit?: string|null}  $metadata
     */
    private function storeImage(UploadedFile $file, array $definition, array $metadata, ?User $uploader, string $originalName, string $publicDisk): MediaAsset
    {
        $sourceDisk = (string) config('maracuja.media.originals_disk', 'local');
        $sourceDirectory = trim((string) config('maracuja.media.originals_directory', 'media/originals'), '/')
            .'/'.now()->format('Y/m');
        $sourcePath = $file->storeAs($sourceDirectory, Str::ulid().'.'.$definition['extension'], $sourceDisk);

        if (! is_string($sourcePath)) {
            throw ValidationException::withMessages(['file' => 'L’original n’a pas pu être conservé.']);
        }

        $image = null;

        try {
            $image = $this->images->create($sourceDisk, $sourcePath, $publicDisk);

            return MediaAsset::query()->create([
                'type' => MediaType::Image,
                'disk' => $publicDisk,
                'source_disk' => $sourceDisk,
                'source_path' => $sourcePath,
                'path' => $image['path'],
                'variants' => $image['variants'],
                'original_name' => $originalName,
                'display_name' => filled($metadata['display_name'] ?? null)
                    ? trim((string) $metadata['display_name'])
                    : pathinfo($originalName, PATHINFO_FILENAME),
                'mime_type' => $definition['mime_type'],
                'extension' => 'webp',
                'size' => $image['size'],
                'width' => $image['width'],
                'height' => $image['height'],
                'alt_text' => $metadata['alt_text'] ?? null,
                'caption' => $metadata['caption'] ?? null,
                'credit' => $metadata['credit'] ?? null,
                'checksum' => hash_file('sha256', Storage::disk($sourceDisk)->path($sourcePath)),
                'uploaded_by' => $uploader?->getKey(),
            ])->refresh();
        } catch (Throwable $exception) {
            Storage::disk($sourceDisk)->delete($sourcePath);
            Storage::disk($publicDisk)->delete(collect($image['variants'] ?? [])->pluck('path')->all());

            throw $exception;
        }
    }

    /** @return array{type: string, extension: string, mime_type: string} */
    private function definitionFor(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();
        $definition = config("maracuja.media.mime_types.{$mimeType}");

        if (! is_array($definition)) {
            throw ValidationException::withMessages([
                'file' => 'Ce type de fichier n’est pas autorisé dans la médiathèque.',
            ]);
        }

        return [...$definition, 'mime_type' => $mimeType];
    }

    private function validateSize(UploadedFile $file, MediaType $type): void
    {
        $maximumKilobytes = (int) config(match ($type) {
            MediaType::Image => 'maracuja.media.image_max_size_kb',
            MediaType::Document => 'maracuja.media.document_max_size_kb',
            MediaType::Video => 'maracuja.media.video_max_size_kb',
        });

        if ($file->getSize() <= $maximumKilobytes * 1024) {
            return;
        }

        throw ValidationException::withMessages([
            'file' => "Le fichier dépasse la taille maximale de {$maximumKilobytes} Ko.",
        ]);
    }

    private function directoryFor(MediaType $type): string
    {
        $root = (string) config(match ($type) {
            MediaType::Image => 'maracuja.media.images_directory',
            MediaType::Document => 'maracuja.media.documents_directory',
            MediaType::Video => 'maracuja.media.videos_directory',
        }, match ($type) {
            MediaType::Image => 'media/images',
            MediaType::Document => 'media/documents',
            MediaType::Video => 'media/videos',
        });

        return trim($root, '/').'/'.now()->format('Y/m');
    }

    private function cleanOriginalName(string $name): string
    {
        $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', basename($name));

        return Str::limit(trim((string) $cleanName), 255, '');
    }
}
