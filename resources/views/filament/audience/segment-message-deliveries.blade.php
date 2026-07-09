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

        <div class="overflow-x-auto rounded-lg" style="border: 1px solid #d1d5db;">
            <table class="min-w-full table-fixed text-center text-sm" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                <thead>
                    <tr class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <th class="font-medium" style="border: 1px solid #d1d5db; padding: 8px;">Ciblés</th>
                        <th class="font-medium" style="border: 1px solid #d1d5db; padding: 8px;">À envoyer</th>
                        <th class="font-medium" style="border: 1px solid #d1d5db; padding: 8px;">Remis au serveur</th>
                        <th class="font-medium" style="border: 1px solid #d1d5db; padding: 8px;">Refus immédiats</th>
                        <th class="font-medium" style="border: 1px solid #d1d5db; padding: 8px;">Exclus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-white">
                        <td class="text-2xl font-semibold text-gray-950" style="border: 1px solid #d1d5db; padding: 10px;">{{ $report['targeted'] }}</td>
                        <td class="text-2xl font-semibold text-info-700" style="border: 1px solid #d1d5db; padding: 10px;">{{ $report['pending'] }}</td>
                        <td class="text-2xl font-semibold text-success-700" style="border: 1px solid #d1d5db; padding: 10px;">{{ $report['accepted'] }}</td>
                        <td class="text-2xl font-semibold text-danger-700" style="border: 1px solid #d1d5db; padding: 10px;">{{ $report['failed'] }}</td>
                        <td class="text-2xl font-semibold text-gray-700" style="border: 1px solid #d1d5db; padding: 10px;">{{ $report['excluded'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    @if ($deliveries->isEmpty())
        <x-filament::section>
            <p class="text-sm text-gray-600">
                Aucune adresse n'a encore été préparée pour cette campagne. Utilisez l'action Planifier pour créer la file d'envoi.
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
                            <button type="button" x-on:click="sortBy('attempts')" class="inline-flex items-center gap-1">
                                Tentatives
                            </button>
                        </th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                            <button type="button" x-on:click="sortBy('attempted')" class="inline-flex items-center gap-1">
                                Dernière tentative
                            </button>
                        </th>
                        <th class="whitespace-nowrap font-medium" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                            <button type="button" x-on:click="sortBy('sent')" class="inline-flex items-center gap-1">
                                Remis le
                            </button>
                        </th>
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
                            data-attempts="{{ str_pad((string) $delivery->attempts, 4, '0', STR_PAD_LEFT) }}"
                            data-attempted="{{ $delivery->attempted_at?->format('Y-m-d H:i:s') ?? '' }}"
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
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">{{ $delivery->attempts }}</td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                                {{ $delivery->attempted_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                                {{ $delivery->sent_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="text-gray-700" style="border: 1px solid #d1d5db; padding: 6px 8px;">
                                {{ $delivery->error_message ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
