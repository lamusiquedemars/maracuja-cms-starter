<x-filament-widgets::widget>
    <x-filament::section heading="Performance des campagnes" :description="$snapshot?->fetched_at ? 'Données consolidées par Cremona, mises à jour '.$snapshot->fetched_at->diffForHumans().'.' : 'Les données apparaîtront après la première synchronisation avec Cremona.'">
        @if ($campaigns === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune campagne n’est encore liée à ce site.</p>
        @else
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-gray-500"><tr><th class="px-3 py-3">Campagne</th><th class="px-3 py-3 text-right">Dépense</th><th class="px-3 py-3 text-right">Leads</th><th class="px-3 py-3 text-right">Convertis</th><th class="px-3 py-3 text-right">Coût / lead</th></tr></thead><tbody class="divide-y dark:divide-white/10">@foreach ($campaigns as $campaign)<tr><td class="px-3 py-3 font-medium">{{ $campaign['name'] }}</td><td class="px-3 py-3 text-right">{{ $campaign['currency'] }} {{ number_format($campaign['spend'], 2, ',', ' ') }}</td><td class="px-3 py-3 text-right">{{ $campaign['leads'] }}</td><td class="px-3 py-3 text-right">{{ $campaign['converted_leads'] }}</td><td class="px-3 py-3 text-right">{{ $campaign['cost_per_lead'] === null ? '—' : $campaign['currency'].' '.number_format($campaign['cost_per_lead'], 2, ',', ' ') }}</td></tr>@endforeach</tbody></table></div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
