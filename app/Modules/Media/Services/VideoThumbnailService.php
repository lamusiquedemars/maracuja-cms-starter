<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Models\MediaAsset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class VideoThumbnailService
{
    public function generate(MediaAsset $media, bool $force = false): bool
    {
        if (! $media->isVideo() || (filled($media->thumbnail_path) && ! $force)) {
            return false;
        }

        $disk = Storage::disk($media->disk);
        $previousPath = $media->thumbnail_path;
        $directory = trim((string) config('maracuja.media.video_thumbnails_directory', 'media/video-thumbnails'), '/');
        $thumbnailPath = $directory.'/'.now()->format('Y/m').'/'.Str::ulid().'.jpg';

        try {
            $disk->makeDirectory(dirname($thumbnailPath));

            $result = Process::timeout(60)->run([
                (string) config('maracuja.media.ffmpeg_binary', 'ffmpeg'),
                '-hide_banner',
                '-loglevel', 'error',
                '-y',
                '-ss', (string) config('maracuja.media.video_thumbnail_second', 1),
                '-i', $disk->path($media->path),
                '-frames:v', '1',
                '-vf', 'scale=960:-2',
                '-q:v', '3',
                $disk->path($thumbnailPath),
            ]);

            if ($result->failed() || ! $disk->exists($thumbnailPath)) {
                $disk->delete($thumbnailPath);
                Log::warning('Video thumbnail generation failed.', [
                    'media_id' => $media->getKey(),
                    'error' => $result->errorOutput(),
                ]);

                return false;
            }

            $media->forceFill(['thumbnail_path' => $thumbnailPath])->save();

            if ($force && filled($previousPath) && $previousPath !== $thumbnailPath) {
                $disk->delete($previousPath);
            }

            return true;
        } catch (Throwable $exception) {
            $disk->delete($thumbnailPath);
            report($exception);

            return false;
        }
    }
}
