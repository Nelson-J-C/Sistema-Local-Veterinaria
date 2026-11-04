<?php

namespace App\Observers;

use App\Enums\ReminderType;
use App\Models\Deworming;
use App\Services\ReminderScheduler;

class DewormingObserver
{
    /**
     * Rebuild the panel reminder after the deworming is stored.
     */
    public function saved(Deworming $deworming): void
    {
        app(ReminderScheduler::class)->syncDeworming($deworming);
    }

    /**
     * Remove pending reminders when a deworming record is deleted.
     */
    public function deleted(Deworming $deworming): void
    {
        app(ReminderScheduler::class)->clear($deworming, ReminderType::Deworming);
    }
}
