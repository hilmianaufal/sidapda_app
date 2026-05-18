<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Carbon;

class ActivityTimeService
{
    public function getActiveActivity(): ?Activity
    {
        $now = Carbon::now();

        $currentTime = $now->format('H:i:s');
        $todayDate = $now->toDateString();
        $todayDay = $now->dayOfWeek;

        return Activity::where('is_active', true)
            ->where(function ($q) use ($todayDay, $todayDate) {

                // kegiatan rutin
                $q->where(function ($r) use ($todayDay) {
                    $r->where('type', 'routine')
                        ->where(function ($dayQuery) use ($todayDay) {
                            $dayQuery->whereJsonContains('days', $todayDay)
                                ->orWhereJsonContains('days', (string) $todayDay);
                        });
                });

                // kegiatan manual
                $q->orWhere(function ($m) use ($todayDate) {
                    $m->where('type', 'manual')
                        ->whereDate('event_date', $todayDate);
                });
            })
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->orderByRaw("CASE WHEN type = 'manual' THEN 0 ELSE 1 END")
            ->orderBy('order')
            ->first();
    }

    public function isLate(Activity $activity): bool
    {
        $lateTime = Carbon::parse($activity->start_time)
            ->addMinutes((int) $activity->late_minutes);

        return Carbon::now()->greaterThan($lateTime);
    }

    public function getActivityStatus(Activity $activity): string
    {
        $now = Carbon::now()->format('H:i:s');

        if ($now < $activity->start_time) {
            return 'soon';
        }

        if ($now >= $activity->start_time && $now <= $activity->end_time) {
            return 'live';
        }

        return 'closed';
    }
}