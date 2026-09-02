<?php

namespace App\Modules\Acquisition\Filament\Widgets;

use App\Modules\Acquisition\Models\AcquisitionReportingSnapshot;
use App\Support\Modules;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;

class AcquisitionCampaignPerformance extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 30;

    protected string $view = 'filament.widgets.acquisition-campaign-performance';

    public static function canView(): bool
    {
        return Modules::enabled('acquisition') && Schema::hasTable('acquisition_reporting_snapshots');
    }

    protected function getViewData(): array
    {
        $snapshot = AcquisitionReportingSnapshot::current();
        $payload = $snapshot?->payload ?? [];
        $currency = $payload['currency'] ?? 'EUR';
        $campaigns = collect($payload['campaigns'] ?? [])->filter('is_array')->map(function (array $campaign) use ($currency): array {
            $spend = (float) ($campaign['spend'] ?? 0);
            $leads = (int) ($campaign['leads'] ?? 0);

            return [
                'name' => $campaign['name'] ?? 'Campagne sans nom',
                'channel' => $campaign['channel'] ?? 'other',
                'spend' => $spend, 'leads' => $leads,
                'converted_leads' => (int) ($campaign['converted_leads'] ?? 0),
                'cost_per_lead' => $leads > 0 ? $spend / $leads : null,
                'currency' => $currency,
            ];
        })->sortByDesc('spend')->values()->all();

        return compact('campaigns', 'snapshot');
    }
}
