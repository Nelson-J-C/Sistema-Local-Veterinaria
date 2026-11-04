<?php

namespace App\Observers;

use App\Enums\ReminderType;
use App\Models\Vaccination;
use App\Models\Vaccine;
use App\Services\ReminderScheduler;
use Illuminate\Support\Carbon;

class VaccinationObserver
{
    /**
     * Default the next dose to the catalog's revaccination interval.
     */
    public function saving(Vaccination $vaccination): void
    {
        if ($vaccination->next_due_at !== null) {
            return;
        }

        $days = Vaccine::query()
            ->whereKey($vaccination->vaccine_id)
            ->value('default_revaccination_days');

        if (filled($days)) {
            $vaccination->next_due_at = Carbon::parse($vaccination->administered_at)->addDays((int) $days);
        }
    }

    /**
     * Rebuild the panel reminder after the vaccination is stored.
     */
    public function saved(Vaccination $vaccination): void
    {
        app(ReminderScheduler::class)->syncVaccination($vaccination);
    }

    /**
     * Remove pending reminders when a vaccination record is deleted.
     */
    public function deleted(Vaccination $vaccination): void
    {
        app(ReminderScheduler::class)->clear($vaccination, ReminderType::Vaccination);
    }
}
