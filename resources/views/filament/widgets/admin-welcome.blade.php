<x-filament-widgets::widget>
    <style>
        .maracuja-admin-dashboard {
            display: grid;
            gap: 1.5rem;
        }

        .maracuja-admin-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.25rem;
            border: 1px solid rgba(245, 158, 11, 0.22);
            border-radius: 1rem;
            background:
                linear-gradient(135deg, rgba(245, 158, 11, 0.12), rgba(255, 255, 255, 0) 45%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(250, 250, 249, 0.88));
        }

        .dark .maracuja-admin-hero {
            border-color: rgba(245, 158, 11, 0.22);
            background:
                linear-gradient(135deg, rgba(245, 158, 11, 0.16), rgba(15, 23, 42, 0) 45%),
                linear-gradient(180deg, rgba(24, 24, 27, 0.9), rgba(9, 9, 11, 0.9));
        }

        .maracuja-admin-kicker {
            margin: 0 0 0.35rem;
            color: rgb(146, 64, 14);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dark .maracuja-admin-kicker {
            color: rgb(251, 191, 36);
        }

        .maracuja-admin-title {
            margin: 0;
            color: rgb(24, 24, 27);
            font-size: clamp(1.45rem, 2vw, 2rem);
            font-weight: 750;
            line-height: 1.15;
        }

        .dark .maracuja-admin-title {
            color: rgb(250, 250, 250);
        }

        .maracuja-admin-baseline {
            max-width: 48rem;
            margin: 0.55rem 0 0;
            color: rgb(82, 82, 91);
            font-size: 0.95rem;
            line-height: 1.65;
        }

        .dark .maracuja-admin-baseline {
            color: rgb(212, 212, 216);
        }

        .maracuja-admin-site-link {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            min-height: 2.5rem;
            padding: 0.65rem 1rem;
            border-radius: 0.7rem;
            background: rgb(217, 119, 6);
            color: white;
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 10px 24px rgba(217, 119, 6, 0.18);
            transition: transform 160ms ease, background 160ms ease, box-shadow 160ms ease;
        }

        .maracuja-admin-site-link:hover {
            background: rgb(180, 83, 9);
            box-shadow: 0 14px 28px rgba(217, 119, 6, 0.24);
            transform: translateY(-1px);
        }

        .maracuja-admin-actions {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .maracuja-admin-action {
            display: grid;
            gap: 0.55rem;
            min-height: 8.75rem;
            padding: 1rem;
            border: 1px solid rgb(228, 228, 231);
            border-radius: 0.85rem;
            background: rgb(255, 255, 255);
            color: inherit;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(24, 24, 27, 0.04);
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        .dark .maracuja-admin-action {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.045);
        }

        .maracuja-admin-action:hover {
            border-color: rgba(217, 119, 6, 0.45);
            box-shadow: 0 16px 35px rgba(24, 24, 27, 0.08);
            transform: translateY(-2px);
        }

        .maracuja-admin-action-marker {
            width: 2.25rem;
            height: 0.35rem;
            border-radius: 999px;
            background: rgb(245, 158, 11);
        }

        .maracuja-admin-action-title {
            color: rgb(24, 24, 27);
            font-size: 0.95rem;
            font-weight: 750;
            line-height: 1.35;
        }

        .dark .maracuja-admin-action-title {
            color: rgb(250, 250, 250);
        }

        .maracuja-admin-action-description {
            color: rgb(82, 82, 91);
            font-size: 0.875rem;
            line-height: 1.55;
        }

        .dark .maracuja-admin-action-description {
            color: rgb(212, 212, 216);
        }

        .maracuja-admin-secondary {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            padding-top: 0.15rem;
        }

        .maracuja-admin-secondary-link {
            display: inline-flex;
            align-items: center;
            min-height: 2.25rem;
            padding: 0.48rem 0.75rem;
            border: 1px solid rgb(228, 228, 231);
            border-radius: 999px;
            color: rgb(63, 63, 70);
            font-size: 0.85rem;
            font-weight: 650;
            text-decoration: none;
            transition: border-color 160ms ease, color 160ms ease, background 160ms ease;
        }

        .dark .maracuja-admin-secondary-link {
            border-color: rgba(255, 255, 255, 0.1);
            color: rgb(228, 228, 231);
        }

        .maracuja-admin-secondary-link:hover {
            border-color: rgba(217, 119, 6, 0.45);
            background: rgba(245, 158, 11, 0.08);
            color: rgb(146, 64, 14);
        }

        .dark .maracuja-admin-secondary-link:hover {
            color: rgb(251, 191, 36);
        }

        @media (max-width: 1024px) {
            .maracuja-admin-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .maracuja-admin-hero {
                flex-direction: column;
                padding: 1rem;
            }

            .maracuja-admin-site-link {
                width: 100%;
            }

            .maracuja-admin-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <x-filament::section>
        <div class="maracuja-admin-dashboard">
            <div class="maracuja-admin-hero">
                <div>
                    <p class="maracuja-admin-kicker">Tableau de bord</p>
                    <h2 class="maracuja-admin-title">
                        {{ $siteName }}
                    </h2>

                    @if (filled($baseline))
                        <p class="maracuja-admin-baseline">
                            {{ $baseline }}
                        </p>
                    @endif
                </div>

                <a
                    href="{{ url('/') }}"
                    target="_blank"
                    rel="noreferrer"
                    class="maracuja-admin-site-link"
                >
                    Voir le site
                </a>
            </div>

            @if (count($primaryActions) > 0)
                <div class="maracuja-admin-actions">
                    @foreach ($primaryActions as $action)
                        <a
                            href="{{ $action['url'] }}"
                            @if ($action['external']) target="_blank" rel="noreferrer" @endif
                            class="maracuja-admin-action"
                        >
                            <span class="maracuja-admin-action-marker" aria-hidden="true"></span>
                            <span class="maracuja-admin-action-title">
                                {{ $action['label'] }}
                            </span>
                            <span class="maracuja-admin-action-description">
                                {{ $action['description'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if (count($secondaryActions) > 0)
                <div class="maracuja-admin-secondary">
                    @foreach ($secondaryActions as $action)
                        <a
                            href="{{ $action['url'] }}"
                            @if ($action['external']) target="_blank" rel="noreferrer" @endif
                            class="maracuja-admin-secondary-link"
                            title="{{ $action['description'] }}"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
