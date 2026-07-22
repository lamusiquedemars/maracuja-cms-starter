@php($media = $getRecord())

<div class="overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800" style="aspect-ratio: 4 / 3">
    @if ($media->isImage())
        <img
            src="{{ $media->url() }}"
            alt=""
            class="h-full w-full object-cover"
            loading="lazy"
        >
    @else
        <div class="flex h-full items-center justify-center text-gray-400">
            <x-filament::icon icon="heroicon-o-document-text" class="h-14 w-14" />
        </div>
    @endif
</div>
