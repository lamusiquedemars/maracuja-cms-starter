<x-filament-panels::page>
    @php
        $conversation = $this->getRecord()->load(['contact', 'assignedUser', 'inquiry', 'messages.authorUser']);
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="space-y-4 lg:col-span-2">
            @forelse ($conversation->messages->sortBy('sent_at') as $message)
                <article @class([
                    'rounded-xl border p-4',
                    'border-primary-200 bg-primary-50 dark:border-primary-800 dark:bg-primary-950' => $message->author_type->value === 'visitor',
                    'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' => $message->author_type->value !== 'visitor',
                    'border-dashed opacity-80' => $message->visibility->value === 'internal',
                ])>
                    <header class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-500">
                        <strong>
                            {{ match ($message->author_type->value) {
                                'visitor' => 'Visiteur',
                                'ai' => 'Assistant IA',
                                'human' => $message->authorUser?->name ?? 'Équipe',
                                default => 'Système',
                            } }}
                            @if ($message->visibility->value === 'internal')
                                · Note interne — invisible pour le visiteur
                            @endif
                        </strong>
                        <time>{{ $message->sent_at?->format('d/m/Y H:i') }}</time>
                    </header>
                    <div class="whitespace-pre-wrap">{{ $message->content }}</div>
                </article>
            @empty
                <p class="text-gray-500">Aucun message.</p>
            @endforelse
        </section>

        <aside class="space-y-6">
            <x-filament::section heading="À traiter">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Référence</dt>
                        <dd>{{ $conversation->public_reference }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Statut</dt>
                        <dd>{{ $conversation->status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Urgence</dt>
                        <dd>{{ $conversation->urgency->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Responsable</dt>
                        <dd>{{ $conversation->assignedUser?->name ?? 'Non attribuée' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Dernière interaction</dt>
                        <dd>{{ $conversation->last_message_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    @if ($conversation->handover_reason)
                        <div>
                            <dt class="text-gray-500">Pourquoi cette demande apparaît ici</dt>
                            <dd>{{ $conversation->handover_reason->label() }}</dd>
                        </div>
                    @endif
                    @if ($conversation->inquiry)
                        <div>
                            <dt class="text-gray-500">Demande reçue</dt>
                            <dd>
                                <a class="text-primary-600 hover:underline" href="{{ \App\Modules\Inquiries\Filament\Resources\Inquiries\InquiryResource::getUrl() }}">
                                    #{{ $conversation->inquiry->id }} · ouvrir les demandes
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-filament::section>

            <x-filament::section heading="Résumé">
                <p class="whitespace-pre-wrap text-sm">{{ $conversation->summary ?: 'Pas encore de résumé.' }}</p>
            </x-filament::section>

            <x-filament::section heading="Contact">
                @if ($conversation->contact)
                    <dl class="space-y-2 text-sm">
                        <div>{{ $conversation->contact->display_name ?: trim($conversation->contact->first_name.' '.$conversation->contact->last_name) }}</div>
                        <div>{{ $conversation->contact->email }}</div>
                        <div>{{ $conversation->contact->phone }}</div>
                    </dl>
                @else
                    <p class="text-sm text-gray-500">Visiteur non identifié.</p>
                @endif
            </x-filament::section>

            <details class="rounded-xl border border-gray-200 bg-white p-4 text-sm dark:border-gray-700 dark:bg-gray-900">
                <summary class="cursor-pointer font-medium">Informations techniques</summary>
                <dl class="mt-4 space-y-3">
                    <div>
                        <dt class="text-gray-500">Canal d’origine</dt>
                        <dd>{{ $conversation->channel->value }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Conversation créée le</dt>
                        <dd>{{ $conversation->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">IA active</dt>
                        <dd>{{ $conversation->ai_enabled ? 'Oui' : 'Non' }}</dd>
                    </div>
                    @if ($conversation->human_handover_at)
                        <div>
                            <dt class="text-gray-500">Transfert déclenché le</dt>
                            <dd>{{ $conversation->human_handover_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </details>
        </aside>
    </div>
</x-filament-panels::page>
