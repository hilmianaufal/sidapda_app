<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class ReportingWeek
{
    public static function currentKey(?Carbon $date = null): string
    {
        $start = ($date ?? now())->copy()->startOfWeek(Carbon::SATURDAY);

        return $start->copy()->addDays(2)->format('o-\WW');
    }

    /**
     * Return the Saturday-Friday range represented by an HTML week input value.
     * The ISO Monday in that value belongs to the reporting week that began two
     * days earlier, on Saturday.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function fromKey(?string $week): array
    {
        if (! is_string($week) || ! preg_match('/^\d{4}-W\d{2}$/', $week)) {
            $week = self::currentKey();
        }

        $monday = Carbon::parse(str_replace('-W', 'W', $week))
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();
        $start = $monday->copy()->subDays(2);
        $end = $start->copy()->addDays(6)->endOfDay();

        return [$start, $end];
    }
}
