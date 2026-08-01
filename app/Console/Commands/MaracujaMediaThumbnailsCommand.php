<?php

namespace App\Console\Commands;

use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Services\VideoThumbnailService;
use Illuminate\Console\Command;

class MaracujaMediaThumbnailsCommand extends Command
{
    protected $signature = 'maracuja:media:thumbnails
        {--force : Régénère aussi les vignettes existantes}';

    protected $description = 'Génère les vignettes manquantes des vidéos de la médiathèque.';

    public function handle(VideoThumbnailService $thumbnails): int
    {
        $force = (bool) $this->option('force');
        $generated = 0;
        $failed = 0;

        MediaAsset::query()
            ->videos()
            ->when(! $force, fn ($query) => $query->whereNull('thumbnail_path'))
            ->orderBy('id')
            ->eachById(function (MediaAsset $media) use ($thumbnails, $force, &$generated, &$failed): void {
                if ($thumbnails->generate($media, $force)) {
                    $generated++;
                    $this->line("✓ {$media->display_name}");
                } else {
                    $failed++;
                    $this->warn("Échec : {$media->display_name}");
                }
            });

        $this->info("{$generated} vignette(s) générée(s), {$failed} échec(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
