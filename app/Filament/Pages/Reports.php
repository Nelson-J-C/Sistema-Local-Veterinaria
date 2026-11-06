<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Models\User;
use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static ?string $title = 'Reportes';

    protected static ?string $navigationLabel = 'Reportes';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Reports;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 1;

    public string $from = '';

    public string $until = '';

    public ?int $vetId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.view') ?? false;
    }

    public function mount(): void
    {
        Gate::authorize('reports.view');

        $this->from = now()->startOfMonth()->toDateString();
        $this->until = today()->toDateString();
    }

    public function updatedVetId(mixed $value): void
    {
        $this->vetId = filled($value) ? (int) $value : null;
    }

    /**
     * Build the data required by the report view.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $from = Carbon::parse($this->from !== '' ? $this->from : now()->startOfMonth())->startOfDay();
        $until = Carbon::parse($this->until !== '' ? $this->until : today())->endOfDay();

        if ($from->greaterThan($until)) {
            [$from, $until] = [$until->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $service = app(ReportService::class);
        $salesByDay = $service->salesByDay($from, $until);

        return [
            'rangeStart' => $from,
            'rangeEnd' => $until,
            'salesByDay' => $salesByDay,
            'salesTotals' => $service->salesTotals($salesByDay),
            'paymentsByMethod' => $service->paymentsByMethod($from, $until),
            'appointmentsByStatus' => $service->appointmentsByStatus($from, $until, $this->vetId),
            'appointmentsByVet' => $service->appointmentsByVet($from, $until, $this->vetId),
            'lowStockProducts' => $service->lowStockProducts(),
            'expiringBatches' => $service->expiringBatches(),
            'vets' => User::query()
                ->where('active', true)
                ->role(['veterinario', 'admin'])
                ->orderBy('name')
                ->get(),
        ];
    }
}
