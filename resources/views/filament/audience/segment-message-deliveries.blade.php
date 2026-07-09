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
    <x-filament::section>
        <x-slot name="heading">Vue d'ensemble</x-slot>

        <div class="grid gap-3 md:grid-cols-5">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Ciblés</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950">{{ $report['targeted'] }}</div>
                <div class="mt-1 text-xs text-gray-500">Périmètre du segment</div>
            </div>
            <div class="rounded-lg border border-info-200 bg-info-50 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-info-700">À envoyer</div>
                <div class="mt-2 text-2xl font-semibold text-info-950">{{ $report['pending'] }}</div>
                <div class="mt-1 text-xs text-info-700">Reste à traiter</div>
            </div>
            <div class="rounded-lg border border-success-200 bg-success-50 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-success-700">Remis au serveur</div>
                <div class="mt-2 text-2xl font-semibold text-success-950">{{ $report['accepted'] }}</div>
                <div class="mt-1 text-xs text-success-700">Acceptés à l'envoi</div>
            </div>
            <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-danger-700">Refus immédiats</div>
                <div class="mt-2 text-2xl font-semibold text-danger-950">{{ $report['failed'] }}</div>
                <div class="mt-1 text-xs text-danger-700">À vérifier ou relancer</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-600">Exclus</div>
                <div class="mt-2 text-2xl font-semibold text-gray-950">{{ $report['excluded'] }}</div>
                <div class="mt-1 text-xs text-gray-500">Hors envoi</div>
            </div>
        </div>
    </x-filament::section>

    @if ($deliveries->isEmpty())
        <x-filament::section>
            <p class="text-sm text-gray-600">
                Aucune adresse n'a encore été préparée pour cette campagne. Utilisez l'action Planifier pour créer la file d'envoi.
            </p>
        </x-filament::section>
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
