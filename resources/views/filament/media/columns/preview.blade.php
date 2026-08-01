@php($media = $getRecord())

<div
    class="rounded-lg bg-gray-100 dark:bg-gray-800"
    style="position: relative; display: block; width: 100%; max-width: 100%; min-width: 0; aspect-ratio: 4 / 3; overflow: hidden"
>
    @if ($media->isImage())
        <img
            src="{{ $media->url() }}"
            alt=""
            style="position: absolute; inset: 0; display: block; width: 100%; height: 100%; max-width: 100%; object-fit: cover"
            loading="lazy"
        >
    @else
        <div class="flex items-center justify-center text-gray-400" style="position: absolute; inset: 0">
            <x-filament::icon icon="heroicon-o-document-text" class="h-14 w-14" />
        </div>
    @endif
</div>
