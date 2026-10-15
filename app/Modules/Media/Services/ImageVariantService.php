<?php

namespace App\Modules\Media\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Imagick;
use ImagickException;

class ImageVariantService
{
    /**
     * @return array{path: string, width: int, height: int, size: int, variants: list<array{path: string, width: int, height: int, size: int}>}
     */
    public function create(string $sourceDisk, string $sourcePath, string $publicDisk): array
    {
        $source = Storage::disk($sourceDisk)->path($sourcePath);

        if (class_exists(Imagick::class)) {
            return $this->createWithImagick($source, $publicDisk);
        }

        return $this->createWithGd($source, $publicDisk);
    }

    /** @return array{path: string, width: int, height: int, size: int, variants: list<array{path: string, width: int, height: int, size: int}>} */
    private function createWithImagick(string $source, string $publicDisk): array
    {
        $created = [];

        try {
            $image = new Imagick($source);
            $image->setIteratorIndex(0);
            $image->autoOrient();
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $this->validateDimensions($width, $height);
            $variants = [];

            $basename = (string) Str::ulid();

            foreach ($this->widthsFor($width) as $targetWidth) {
                $variant = clone $image;
                $variant->scaleImage($targetWidth, 0);
                $variant->stripImage();
                $variant->setImageFormat('webp');
                $variant->setImageCompression(Imagick::COMPRESSION_JPEG);
                $variant->setImageCompressionQuality((int) config('maracuja.media.image_webp_quality', 82));
                $path = $this->variantPath($basename, $targetWidth);
                Storage::disk($publicDisk)->put($path, $variant->getImagesBlob(), 'public');
                $created[] = $path;
                $variants[] = $this->variant($publicDisk, $path, $variant->getImageWidth(), $variant->getImageHeight());
                $variant->clear();
            }

            $image->clear();

            return $this->result($variants);
        } catch (ImagickException $exception) {
            Storage::disk($publicDisk)->delete($created);

            throw ValidationException::withMessages([
                'file' => 'Cette image ne peut pas être préparée par le serveur. Essayez un JPEG, PNG ou WebP.',
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($publicDisk)->delete($created);

            throw $exception;
        }
    }

    /** @return array{path: string, width: int, height: int, size: int, variants: list<array{path: string, width: int, height: int, size: int}>} */
    private function createWithGd(string $source, string $publicDisk): array
    {
        if (! function_exists('imagewebp')) {
            throw ValidationException::withMessages([
                'file' => 'Le serveur ne peut pas encore préparer les images. Contactez l’administrateur du site.',
            ]);
        }

        $info = @getimagesize($source);
        $image = match (is_array($info) ? $info[2] : null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => false,
        };

        if ($image === false) {
            throw ValidationException::withMessages([
                'file' => 'Cette image nécessite Imagick pour être préparée. Essayez un JPEG, PNG ou WebP.',
            ]);
        }

        if (is_array($info) && $info[2] === IMAGETYPE_JPEG) {
            $image = $this->orientJpeg($image, $source);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $this->validateDimensions($width, $height);
        $created = [];

        try {
            $basename = (string) Str::ulid();

            foreach ($this->widthsFor($width) as $targetWidth) {
                $targetHeight = max(1, (int) round($height * $targetWidth / $width));
                $variant = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($variant, false);
                imagesavealpha($variant, true);
                imagefill($variant, 0, 0, imagecolorallocatealpha($variant, 0, 0, 0, 127));
                imagecopyresampled($variant, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
                ob_start();
                $written = imagewebp($variant, null, (int) config('maracuja.media.image_webp_quality', 82));
                $contents = ob_get_clean();
                imagedestroy($variant);

                if (! $written || ! is_string($contents)) {
                    throw new \RuntimeException('La variante WebP n’a pas pu être créée.');
                }

                $path = $this->variantPath($basename, $targetWidth);
                Storage::disk($publicDisk)->put($path, $contents, 'public');
                $created[] = $path;
                $variants[] = $this->variant($publicDisk, $path, $targetWidth, $targetHeight);
            }

            return $this->result($variants);
        } catch (\Throwable $exception) {
            Storage::disk($publicDisk)->delete($created);

            throw $exception;
        } finally {
            imagedestroy($image);
        }
    }

    /** @return list<int> */
    private function widthsFor(int $width): array
    {
        return collect((array) config('maracuja.media.image_variant_widths', [640, 960, 1440, 1920]))
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0 && $value <= $width)
            ->push($width)->unique()->sort()->values()->all();
    }

    private function variantPath(string $basename, int $width): string
    {
        $directory = trim((string) config('maracuja.media.images_directory', 'media/images'), '/')
            .'/'.now()->format('Y/m');

        return $directory.'/'.$basename.'-'.$width.'.webp';
    }

    /** @return array{path: string, width: int, height: int, size: int} */
    private function variant(string $disk, string $path, int $width, int $height): array
    {
        return ['path' => $path, 'width' => $width, 'height' => $height, 'size' => Storage::disk($disk)->size($path)];
    }

    /** @param list<array{path: string, width: int, height: int, size: int}> $variants */
    private function result(array $variants): array
    {
        $primary = $variants[array_key_last($variants)];

        return [...$primary, 'variants' => $variants];
    }

    private function validateDimensions(int $width, int $height): void
    {
        if ($width < 1 || $height < 1 || $width * $height > (int) config('maracuja.media.image_max_pixels', 36_000_000)) {
            throw ValidationException::withMessages(['file' => 'Cette image est trop grande pour être préparée en toute sécurité.']);
        }
    }

    private function orientJpeg(\GdImage $image, string $source): \GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($source) : false;
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, in_array($orientation, [2, 5, 7], true) ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);
        }
        if (in_array($orientation, [3, 4], true)) {
            $image = imagerotate($image, 180, 0);
        }
        if (in_array($orientation, [5, 6], true)) {
            $image = imagerotate($image, -90, 0);
        }
        if (in_array($orientation, [7, 8], true)) {
            $image = imagerotate($image, 90, 0);
        }

        return $image;
    }
}
