<?php

namespace App\Services;

use App\Models\BlockedPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BlockedPeriodService
{
    public function getBlockedPeriodsForDate(string $date): Collection
    {
        $day = Carbon::parse($date);
        $weekday = $day->dayOfWeek;

        return BlockedPeriod::query()
            ->where(function ($q) use ($day, $weekday) {

                $q->where(function ($q) use ($day) {
                    $q->where('is_recurring', false)
                        ->whereDate('date', $day);
                });

                $q->orWhere(function ($q) {
                    $q->where('is_recurring', true)
                        ->whereNull('weekday');
                });

                $q->orWhere(function ($q) use ($weekday) {
                    $q->where('is_recurring', true)
                        ->where('weekday', $weekday);
                });
            })
            ->get();
    }

    public function isDayBlocked(string $date): bool
    {
        return $this->getBlockedPeriodsForDate($date)
            ->contains(fn($b) => $b->is_full_day === true);
    }

    public function getBlockedTimes(string $date, int $slotMinutes = 30): array
    {
        $blocked = [];
        $blocks = $this->getBlockedPeriodsForDate($date);

        foreach ($blocks as $block) {
            if ($block->is_full_day) {
                return ['FULL_DAY'];
            }

            if ($block->start_time && $block->end_time) {
                $start = Carbon::createFromFormat('H:i:s', $block->start_time);
                $end   = Carbon::createFromFormat('H:i:s', $block->end_time);

                while ($start < $end) {
                    $blocked[] = $start->format('H:i');
                    $start->addMinutes($slotMinutes);
                }
            }
        }

        return array_values(array_unique($blocked));
    }

    public function canSchedule(
        string $date,
        string $time,
        int $requiredSlots = 1,
        int $slotMinutes = 30
    ): bool {
        if ($this->isDayBlocked($date)) {
            return false;
        }

        $blockedTimes = $this->getBlockedTimes($date, $slotMinutes);

        $current = Carbon::createFromFormat('H:i', $time);

        for ($i = 0; $i < $requiredSlots; $i++) {
            if (in_array($current->format('H:i'), $blockedTimes, true)) {
                return false;
            }
            $current->addMinutes($slotMinutes);
        }

        return true;
    }
}
