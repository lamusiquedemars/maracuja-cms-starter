<div class="grid gap-6 md:grid-cols-2">
    <div class="overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
        @if ($media->isImage())
            <img src="{{ $media->url() }}" alt="{{ $media->alt_text }}" class="h-auto w-full object-contain">
        @else
            <div class="flex min-h-64 flex-col items-center justify-center gap-4 p-8 text-gray-500">
                <x-filament::icon icon="heroicon-o-document-text" class="h-16 w-16" />
                <a href="{{ $media->url() }}" target="_blank" rel="noopener" class="font-medium text-primary-600 hover:underline">
                    Ouvrir le document
                </a>
            </div>
        @endif
    </div>

    <dl class="grid content-start gap-4 text-sm">
        <div><dt class="font-medium text-gray-950 dark:text-white">Nom original</dt><dd class="text-gray-600 dark:text-gray-300">{{ $media->original_name }}</dd></div>
        <div><dt class="font-medium text-gray-950 dark:text-white">Type</dt><dd class="text-gray-600 dark:text-gray-300">{{ $media->mime_type }}</dd></div>
        <div><dt class="font-medium text-gray-950 dark:text-white">Dimensions</dt><dd class="text-gray-600 dark:text-gray-300">{{ $media->dimensionsLabel() ?? '—' }}</dd></div>
        <div><dt class="font-medium text-gray-950 dark:text-white">Poids</dt><dd class="text-gray-600 dark:text-gray-300">{{ $media->formattedSize() }}</dd></div>
        <div><dt class="font-medium text-gray-950 dark:text-white">Texte alternatif</dt><dd class="text-gray-600 dark:text-gray-300">{{ $media->alt_text ?: '—' }}</dd></div>
        <div><dt class="font-medium text-gray-950 dark:text-white">Légende</dt><dd class="text-gray-600 dark:text-gray-300">{{ $media->caption ?: '—' }}</dd></div>
        <div><dt class="font-medium text-gray-950 dark:text-white">Crédit</dt><dd class="text-gray-600 dark:text-gray-300">{{ $media->credit ?: '—' }}</dd></div>
        <div><dt class="font-medium text-gray-950 dark:text-white">Utilisations</dt><dd class="text-gray-600 dark:text-gray-300">{{ $media->usages()->count() }}</dd></div>
    </dl>
</div>
