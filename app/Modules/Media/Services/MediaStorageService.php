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
    /**
     * @param  array{display_name?: string|null, alt_text?: string|null, caption?: string|null, credit?: string|null}  $metadata
     */
    public function store(UploadedFile $file, array $metadata = [], ?User $uploader = null): MediaAsset
    {
        $definition = $this->definitionFor($file);
        $type = MediaType::from($definition['type']);
        $this->validateSize($file, $type);

        $disk = (string) config('maracuja.media.disk', 'public');
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
            $originalName = $this->cleanOriginalName($file->getClientOriginalName());

            return MediaAsset::query()->create([
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
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

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
        $maximumKilobytes = (int) config(
            $type === MediaType::Image ? 'maracuja.media.image_max_size_kb' : 'maracuja.media.document_max_size_kb'
        );

        if ($file->getSize() <= $maximumKilobytes * 1024) {
            return;
        }

        throw ValidationException::withMessages([
            'file' => "Le fichier dépasse la taille maximale de {$maximumKilobytes} Ko.",
        ]);
    }

    private function directoryFor(MediaType $type): string
    {
        $root = (string) config(
            $type === MediaType::Image ? 'maracuja.media.images_directory' : 'maracuja.media.documents_directory'
        );

        return trim($root, '/').'/'.now()->format('Y/m');
    }

    private function cleanOriginalName(string $name): string
    {
        $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', basename($name));

        return Str::limit(trim((string) $cleanName), 255, '');
    }
}
