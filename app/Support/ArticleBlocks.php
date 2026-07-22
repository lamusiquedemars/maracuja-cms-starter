<?php

namespace App\Support;

use App\Modules\Media\Models\MediaAsset;
use Illuminate\Support\HtmlString;

class ArticleBlocks
{
    public static function render(?array $blocks): HtmlString
    {
        $media = MediaAsset::query()
            ->whereIn('id', collect($blocks ?? [])->pluck('media_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $html = collect($blocks ?? [])
            ->map(fn (array $block): string => self::renderBlock($block, $media->get($block['media_id'] ?? null)))
            ->filter()
            ->implode('');

        return new HtmlString($html);
    }

    private static function renderBlock(array $block, ?MediaAsset $media): string
    {
        return match ($block['type'] ?? null) {
            'heading' => self::heading($block),
            'rich_text' => self::richText($block),
            'image' => self::image($block, $media),
            'quote' => self::quote($block),
            'note' => self::note($block),
            'table' => self::table($block),
            default => '',
        };
    }

    private static function heading(array $block): string
    {
        $text = trim((string) ($block['heading'] ?? ''));
        if ($text === '') {
            return '';
        }

        $level = in_array((string) ($block['level'] ?? '2'), ['2', '3'], true)
            ? (string) $block['level']
            : '2';

        return sprintf('<h%s>%s</h%s>', $level, e($text), $level);
    }

    private static function richText(array $block): string
    {
        $text = trim((string) ($block['text'] ?? ''));
        if ($text === '') {
            return '';
        }

        return '<div class="article-block article-block--text">'
            . self::cleanRichText($text)
            . '</div>';
    }

    private static function image(array $block, ?MediaAsset $media): string
    {
        $src = $media?->url() ?? trim((string) ($block['image_path'] ?? ''));
        if ($src === '') {
            return '';
        }

        $resolvedSrc = str_starts_with($src, '/') || str_starts_with($src, 'http') ? $src : asset('storage/' . $src);
        $caption = trim((string) ($block['caption'] ?? $media?->caption ?? ''));
        $alt = trim((string) ($block['alt'] ?? $media?->alt_text ?? ''));

        return '<figure class="media-figure article-block article-block--image">'
            . sprintf('<img src="%s" alt="%s" loading="lazy" decoding="async">', e($resolvedSrc), e($alt))
            . ($caption !== '' ? '<figcaption class="media-figure__caption"><span>' . e($caption) . '</span></figcaption>' : '')
            . '</figure>';
    }

    private static function quote(array $block): string
    {
        $quote = trim((string) ($block['quote'] ?? ''));
        if ($quote === '') {
            return '';
        }

        $author = trim((string) ($block['author'] ?? ''));

        return '<figure class="quote article-block article-block--quote">'
            . '<blockquote class="quote__text">' . e($quote) . '</blockquote>'
            . ($author !== '' ? '<figcaption class="quote__meta">' . e($author) . '</figcaption>' : '')
            . '</figure>';
    }

    private static function note(array $block): string
    {
        $text = trim((string) ($block['note'] ?? ''));
        if ($text === '') {
            return '';
        }

        return '<aside class="article-note article-block">'
            . self::cleanRichText($text)
            . '</aside>';
    }

    private static function table(array $block): string
    {
        $rows = collect(preg_split('/\R/', (string) ($block['table_rows'] ?? '')))
            ->map(fn (string $row): array => array_map('trim', explode('|', $row)))
            ->filter(fn (array $columns): bool => collect($columns)->filter()->isNotEmpty())
            ->values();

        if ($rows->isEmpty()) {
            return '';
        }

        $body = $rows
            ->map(function (array $columns): string {
                return '<tr>' . collect($columns)
                    ->map(fn (string $column): string => '<td>' . e($column) . '</td>')
                    ->implode('') . '</tr>';
            })
            ->implode('');

        return '<div class="article-block article-block--table"><table class="table table--featured"><tbody>'
            . $body
            . '</tbody></table></div>';
    }

    private static function cleanRichText(string $html): string
    {
        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><sup><sub>');
    }
}
