<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Models\Appointment;
use App\Models\User;
use App\Services\ClinicSchedule;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class Agenda extends Page
{
    protected string $view = 'filament.pages.agenda';

    protected static ?string $navigationLabel = 'Agenda';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Clinical;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 25;

    public string $date = '';

    public string $viewMode = 'day';

    public ?int $vetId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('appointments.view') ?? false;
    }

    public function mount(): void
    {
        Gate::authorize('appointments.view');

        $this->date = today()->toDateString();
    }

    public function getTitle(): string
    {
        return $this->viewMode === 'week' ? 'Agenda semanal' : 'Agenda diaria';
    }

    public function previous(): void
    {
        $date = Carbon::parse($this->date);

        $this->date = ($this->viewMode === 'week' ? $date->subWeek() : $date->subDay())->toDateString();
    }

    public function next(): void
    {
        $date = Carbon::parse($this->date);

        $this->date = ($this->viewMode === 'week' ? $date->addWeek() : $date->addDay())->toDateString();
    }

    public function goToToday(): void
    {
        $this->date = today()->toDateString();
    }

    public function updatedVetId(mixed $value): void
    {
        $this->vetId = filled($value) ? (int) $value : null;
    }

    public function updatedViewMode(string $value): void
    {
        $this->viewMode = in_array($value, ['day', 'week'], true) ? $value : 'day';
    }

    /**
     * Build the data required by the agenda view.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $selectedDate = Carbon::parse($this->date)->startOfDay();
        $weekStart = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $isWeek = $this->viewMode === 'week';

        $rangeStart = $isWeek ? $weekStart->copy() : $selectedDate->copy();
        $rangeEnd = $isWeek ? $weekEnd->copy()->endOfDay() : $selectedDate->copy()->endOfDay();

        $schedule = app(ClinicSchedule::class);

        /** @var Collection<int, User> $filterVets */
        $filterVets = User::query()
            ->where('active', true)
            ->role(['veterinario', 'admin'])
            ->orderBy('name')
            ->get();

        $vets = $filterVets
            ->when($this->vetId, fn (Collection $vets): Collection => $vets->where('id', $this->vetId))
            ->values();

        $appointments = Appointment::query()
            ->with(['patient', 'owner', 'vet'])
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->orderBy('starts_at')
            ->get();

        $slotMinutes = max(5, (int) config('clinic.appointment_duration', 30));
        $dayHours = $schedule->hoursFor($selectedDate);
        $holiday = $schedule->holidayFor($selectedDate);
        $slots = [];
        $grid = [];

        if (! $isWeek && $dayHours !== null && $holiday === null) {
            $open = Carbon::parse("{$selectedDate->format('Y-m-d')} {$dayHours['open']}", $selectedDate->getTimezone());
            $close = Carbon::parse("{$selectedDate->format('Y-m-d')} {$dayHours['close']}", $selectedDate->getTimezone());

            for ($slot = $open->copy(); $slot->lessThan($close); $slot->addMinutes($slotMinutes)) {
                $slots[] = $slot->copy();
            }

            $slotCount = count($slots);

            foreach ($appointments as $appointment) {
                if ($appointment->starts_at->lessThan($open) || $appointment->starts_at->greaterThanOrEqualTo($close)) {
                    continue;
                }

                $index = (int) floor($open->diffInMinutes($appointment->starts_at) / $slotMinutes);
                $index = max(0, min($index, $slotCount - 1));

                $grid[$appointment->vet_id][$index][] = $appointment;
            }
        }

        $weekDays = [];

        if ($isWeek) {
            for ($day = $weekStart->copy(); $day->lessThanOrEqualTo($weekEnd); $day->addDay()) {
                $weekDays[] = [
                    'date' => $day->copy(),
                    'label' => $day->copy()->locale('es')->translatedFormat('D d/m'),
                    'holiday' => $schedule->holidayFor($day),
                ];
            }
        }

        $weekGrid = [];

        foreach ($appointments as $appointment) {
            $weekGrid[$appointment->vet_id][$appointment->starts_at->format('Y-m-d')][] = $appointment;
        }

        return [
            'selectedDate' => $selectedDate,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'displayDate' => $isWeek
                ? $weekStart->copy()->locale('es')->translatedFormat('d M Y').' al '.$weekEnd->copy()->locale('es')->translatedFormat('d M Y')
                : $selectedDate->copy()->locale('es')->translatedFormat('l d \d\e F \d\e Y'),
            'isWeek' => $isWeek,
            'vets' => $vets,
            'filterVets' => $filterVets,
            'timeSlots' => $slots,
            'grid' => $grid,
            'weekDays' => $weekDays,
            'weekGrid' => $weekGrid,
            'dayHours' => $dayHours,
            'holiday' => $holiday,
            'isClosed' => $dayHours === null,
            'slotMinutes' => $slotMinutes,
        ];
    }
}
