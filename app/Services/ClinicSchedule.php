<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ClinicSchedule
{
    /**
     * Get the configured opening hours grouped by weekday.
     *
     * @return array<string, array{enabled?: bool, open?: string, close?: string}>
     */
    public function weeklyHours(): array
    {
        $hours = Setting::get('clinic_hours', config('clinic.hours', []), 'appointments');

        if (! is_array($hours)) {
            return config('clinic.hours', []);
        }

        return $hours;
    }

    /**
     * Get the opening hours for a specific date, or null when the clinic is closed.
     *
     * @return array{enabled: bool, open: string, close: string}|null
     */
    public function hoursFor(CarbonInterface $date): ?array
    {
        $hours = $this->weeklyHours()[strtolower($date->format('l'))] ?? null;

        if (! is_array($hours) || ! ($hours['enabled'] ?? false)) {
            return null;
        }

        return [
            'enabled' => true,
            'open' => (string) ($hours['open'] ?? '08:00'),
            'close' => (string) ($hours['close'] ?? '20:00'),
        ];
    }

    /**
     * Get the configured holidays.
     *
     * @return array<int, array{date?: string, name?: string}>
     */
    public function holidays(): array
    {
        $holidays = Setting::get('holidays', config('clinic.holidays', []), 'appointments');

        if (! is_array($holidays)) {
            return [];
        }

        return array_values(array_filter(
            $holidays,
            fn (mixed $holiday): bool => is_array($holiday) && filled($holiday['date'] ?? null),
        ));
    }

    /**
     * Get the holiday that matches a date, if any.
     *
     * @return array{date: string, name: string}|null
     */
    public function holidayFor(CarbonInterface $date): ?array
    {
        $key = $date->format('Y-m-d');

        foreach ($this->holidays() as $holiday) {
            if (($holiday['date'] ?? null) === $key) {
                return [
                    'date' => $key,
                    'name' => (string) ($holiday['name'] ?? 'Feriado'),
                ];
            }
        }

        return null;
    }

    /**
     * Determine whether a date is a holiday.
     */
    public function isHoliday(CarbonInterface $date): bool
    {
        return $this->holidayFor($date) !== null;
    }

    /**
     * Determine whether the clinic is open on a date.
     */
    public function isOpen(CarbonInterface $date): bool
    {
        return $this->hoursFor($date) !== null && ! $this->isHoliday($date);
    }

    /**
     * Determine whether an appointment fits entirely inside the opening hours.
     */
    public function isWithinHours(CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        $hours = $this->hoursFor($startsAt);

        if ($hours === null || ! $startsAt->isSameDay($endsAt)) {
            return false;
        }

        $open = Carbon::parse("{$startsAt->format('Y-m-d')} {$hours['open']}", $startsAt->getTimezone());
        $close = Carbon::parse("{$startsAt->format('Y-m-d')} {$hours['close']}", $startsAt->getTimezone());

        return $startsAt->greaterThanOrEqualTo($open) && $endsAt->lessThanOrEqualTo($close);
    }
}
