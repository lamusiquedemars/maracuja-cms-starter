@php
    $report = $segmentMessage->deliveryReport();
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
    <div class="grid gap-3 md:grid-cols-5">
        <div class="rounded-md border border-gray-200 p-3">
            <div class="text-xs text-gray-500">Ciblés</div>
            <div class="text-lg font-semibold">{{ $report['targeted'] }}</div>
        </div>
        <div class="rounded-md border border-gray-200 p-3">
            <div class="text-xs text-gray-500">À envoyer</div>
            <div class="text-lg font-semibold">{{ $report['pending'] }}</div>
        </div>
        <div class="rounded-md border border-gray-200 p-3">
            <div class="text-xs text-gray-500">Remis au serveur mail</div>
            <div class="text-lg font-semibold">{{ $report['accepted'] }}</div>
        </div>
        <div class="rounded-md border border-gray-200 p-3">
            <div class="text-xs text-gray-500">Refus immédiats</div>
            <div class="text-lg font-semibold">{{ $report['failed'] }}</div>
        </div>
        <div class="rounded-md border border-gray-200 p-3">
            <div class="text-xs text-gray-500">Exclus</div>
            <div class="text-lg font-semibold">{{ $report['excluded'] }}</div>
        </div>
    </div>

    <p class="text-sm text-gray-600">
        “Remis au serveur mail” signifie que le SMTP a accepté le message. Les bounces reçus après coup doivent être importés ou traités séparément.
    </p>

    @if ($deliveries->isEmpty())
        <p class="text-sm text-gray-600">Aucune livraison enregistrée pour ce message.</p>
    @else
        <div class="overflow-x-auto rounded-md border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">
                            <button type="button" x-on:click="sortBy('email')" class="inline-flex items-center gap-1">
                                Email
                            </button>
                        </th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">
                            <button type="button" x-on:click="sortBy('contact')" class="inline-flex items-center gap-1">
                                Contact
                            </button>
                        </th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">
                            <button type="button" x-on:click="sortBy('status')" class="inline-flex items-center gap-1">
                                Statut
                            </button>
                        </th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">
                            <button type="button" x-on:click="sortBy('domain')" class="inline-flex items-center gap-1">
                                Domaine
                            </button>
                        </th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">
                            <button type="button" x-on:click="sortBy('attempts')" class="inline-flex items-center gap-1">
                                Tentatives
                            </button>
                        </th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">
                            <button type="button" x-on:click="sortBy('attempted')" class="inline-flex items-center gap-1">
                                Dernière tentative
                            </button>
                        </th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">
                            <button type="button" x-on:click="sortBy('sent')" class="inline-flex items-center gap-1">
                                Remis le
                            </button>
                        </th>
                        <th class="min-w-72 px-4 py-3 font-medium">Raison</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white" x-ref="rows">
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
                            data-attempts="{{ str_pad((string) $delivery->attempts, 4, '0', STR_PAD_LEFT) }}"
                            data-attempted="{{ $delivery->attempted_at?->format('Y-m-d H:i:s') ?? '' }}"
                            data-sent="{{ $delivery->sent_at?->format('Y-m-d H:i:s') ?? '' }}"
                        >
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $delivery->email }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $contactName ?: '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-filament::badge :color="$delivery->statusColor()">
                                    {{ $delivery->statusLabel() }}
                                </x-filament::badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $delivery->domain() ?: '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $delivery->attempts }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                                {{ $delivery->attempted_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                                {{ $delivery->sent_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $delivery->error_message ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
