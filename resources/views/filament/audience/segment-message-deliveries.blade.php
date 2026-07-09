@php
    $report = $segmentMessage->deliveryReport();
    $summaryColumns = $segmentMessage->usesBrevo()
        ? [
            'targeted' => ['label' => 'Ciblés', 'color' => 'text-gray-950'],
            'sent_to_provider' => ['label' => 'Envoyés à Brevo', 'color' => 'text-info-700'],
            'delivered' => ['label' => 'Délivrés', 'color' => 'text-success-700'],
            'opened' => ['label' => 'Ouverts', 'color' => 'text-success-700'],
            'clicked' => ['label' => 'Cliqués', 'color' => 'text-success-700'],
            'soft_bounced' => ['label' => 'Soft bounces', 'color' => 'text-warning-700'],
            'hard_bounced' => ['label' => 'Hard bounces', 'color' => 'text-danger-700'],
            'unsubscribed' => ['label' => 'Désinscrits', 'color' => 'text-gray-700'],
            'complained' => ['label' => 'Plaintes spam', 'color' => 'text-danger-700'],
            'error' => ['label' => 'Erreurs', 'color' => 'text-danger-700'],
        ]
        : [
            'targeted' => ['label' => 'Ciblés', 'color' => 'text-gray-950'],
            'pending' => ['label' => 'À envoyer', 'color' => 'text-info-700'],
            'accepted' => ['label' => 'Remis au serveur', 'color' => 'text-success-700'],
            'failed' => ['label' => 'Refus immédiats', 'color' => 'text-danger-700'],
            'excluded' => ['label' => 'Exclus', 'color' => 'text-gray-700'],
        ];
@endphp

<div
    class="space-y-4"
    x-data="{
        sortKey: 'email',
        sortDirection: 'asc',
        sortBy(key) {
            if (this.sortKey === key) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                return;
            }

            this.sortKey = key;
            this.sortDirection = 'asc';
        },
        sortedRows() {
            const rows = Array.from(this.$refs.rows.querySelectorAll('tr'));

            rows.sort((a, b) => {
                const left = a.dataset[this.sortKey] || '';
                const right = b.dataset[this.sortKey] || '';
                const result = left.localeCompare(right, 'fr', { numeric: true, sensitivity: 'base' });

                return this.sortDirection === 'asc' ? result : -result;
            });

            rows.forEach((row) => this.$refs.rows.appendChild(row));
        },
    }"
    x-effect="sortedRows()"
>
    <x-filament::section>
        <x-slot name="heading">Vue d'ensemble</x-slot>

        <div class="overflow-x-auto rounded-lg" style="border: 1px solid #d1d5db;">
            <table class="min-w-full table-fixed text-center text-sm" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                <thead>
                    <tr class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        @foreach ($summaryColumns as $column)
                            <th class="font-medium" style="border: 1px solid #d1d5db; padding: 8px;">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-white">
                        @foreach ($summaryColumns as $key => $column)
                            <td class="text-2xl font-semibold {{ $column['color'] }}" style="border: 1px solid #d1d5db; padding: 10px;">{{ $report[$key] ?? 0 }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
        @if ($segmentMessage->usesBrevo())
            <p class="mt-3 text-xs text-gray-600">
                “Envoyés à Brevo” signifie que Brevo a accepté la campagne. Les compteurs de délivrance, ouvertures et clics seront alimentés par les événements Brevo.
            </p>
        @endif
    </x-filament::section>

    @if ($deliveries->isEmpty())
        <x-filament::section>
            <p class="text-sm text-gray-600">
                Aucune adresse n'a encore été préparée pour cette campagne.
            </p>
        </x-filament::section>
    @else
        <div class="overflow-x-auto rounded-md" style="border: 1px solid #d1d5db;">
            <table class="min-w-full text-xs" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                            <button type="button" x-on:click="sortBy('email')" class="inline-flex items-center gap-1">
                                Email
                            </button>
                        </th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                            <button type="button" x-on:click="sortBy('contact')" class="inline-flex items-center gap-1">
                                Contact
                            </button>
                        </th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                            <button type="button" x-on:click="sortBy('status')" class="inline-flex items-center gap-1">
                                Statut
                            </button>
                        </th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                            <button type="button" x-on:click="sortBy('domain')" class="inline-flex items-center gap-1">
                                Domaine
                            </button>
                        </th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                            <button type="button" x-on:click="sortBy('event')" class="inline-flex items-center gap-1">
                                Dernier événement
                            </button>
                        </th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                            <button type="button" x-on:click="sortBy('eventat')" class="inline-flex items-center gap-1">
                                Date dernier événement
                            </button>
                        </th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                            <button type="button" x-on:click="sortBy('sent')" class="inline-flex items-center gap-1">
                                Envoyé le
                            </button>
                        </th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">Délivré le</th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">Ouvert le</th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">Cliqué le</th>
                        <th class="min-w-72 font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">Raison</th>
                    </tr>
                </thead>
                <tbody class="bg-white" x-ref="rows">
                    @foreach ($deliveries as $delivery)
                        @php
                            $contactName = trim(collect([
                                $delivery->contact?->first_name,
                                $delivery->contact?->last_name,
                                $delivery->contact?->organization_name,
                            ])->filter()->join(' '));
                        @endphp

                        <tr
                            data-email="{{ $delivery->email }}"
                            data-contact="{{ $contactName }}"
                            data-status="{{ $delivery->statusLabel() }}"
                            data-domain="{{ $delivery->domain() }}"
                            data-event="{{ $delivery->latest_event ?? '' }}"
                            data-eventat="{{ $delivery->latest_event_at?->format('Y-m-d H:i:s') ?? '' }}"
                            data-sent="{{ $delivery->sent_at?->format('Y-m-d H:i:s') ?? '' }}"
                        >
                            <td class="whitespace-nowrap font-medium text-gray-900" style="border: 1px solid #d1d5db; padding: 6px 8px;">{{ $delivery->email }}</td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">{{ $contactName ?: '-' }}</td>
                            <td class="whitespace-nowrap" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                                <x-filament::badge :color="$delivery->statusColor()">
                                    {{ $delivery->statusLabel() }}
                                </x-filament::badge>
                            </td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">{{ $delivery->domain() ?: '-' }}</td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                                {{ $delivery->latest_event ?: '-' }}
                            </td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                                {{ $delivery->latest_event_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                                {{ $delivery->sent_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">{{ $delivery->delivered_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">{{ $delivery->opened_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">{{ $delivery->clicked_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                                {{ $delivery->bounce_reason ?: ($delivery->error_message ?: '-') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
