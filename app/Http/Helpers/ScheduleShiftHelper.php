<?php

namespace App\Http\Helpers;

use Carbon\Carbon;

/**
 * Helper class for extracting shifts from schedule patterns and determining today's applicable shifts
 */
class ScheduleShiftHelper
{
    /**
     * Normalize schedule pattern into a consistent internal structure.
     * Accepts both array-of-pairs and map forms for daily/weekly/monthly.
     */
    public static function normalizePattern($pattern): array
    {
        if (is_string($pattern)) {
            $decoded = json_decode($pattern, true);
            $pattern = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($pattern)) {
            $pattern = [];
        }

        $norm = [
            'single' => [],        // [shift_id, ...]
            'daily' => [],         // [[day => 'monday', shift => 'SHFT...'], ...]
            'weekly' => [],        // [[week => 1..5, shift => 'SHFT...'], ...]
            'monthly' => [],       // [[month => 1..12, shift => 'SHFT...'], ...]
            'range' => [],         // [[start_date => 'Y-m-d', end_date => 'Y-m-d', shift_id => 'SHFT...'], ...]
            'custom_day' => [],    // [[on => '1'|'2'|'3'|'4'|'last', day => 'monday'.., shift => 'SHFT...'], ...]
        ];

        // single
        if (!empty($pattern['single']) && is_array($pattern['single'])) {
            $norm['single'] = array_values(array_filter($pattern['single'], fn($v) => is_string($v) && $v !== ''));
        }

        // daily can be array-of-pairs or map
        if (!empty($pattern['daily'])) {
            if (is_array($pattern['daily'])) {
                // If it's associative (map): {"tuesday":"SHFT..."}
                $isAssoc = array_keys($pattern['daily']) !== range(0, count($pattern['daily']) - 1);
                if ($isAssoc) {
                    foreach ($pattern['daily'] as $day => $shift) {
                        if (is_string($day) && is_string($shift) && $shift !== '') {
                            $norm['daily'][] = ['day' => strtolower($day), 'shift' => $shift];
                        }
                    }
                } else {
                    // Array-of-pairs: [{label:"tuesday", value:"SHFT..."}, ...]
                    foreach ($pattern['daily'] as $row) {
                        $day = $row['label'] ?? ($row['day'] ?? null);
                        $shift = $row['value'] ?? ($row['shift'] ?? null);
                        if (is_string($day) && is_string($shift) && $shift !== '') {
                            $norm['daily'][] = ['day' => strtolower($day), 'shift' => $shift];
                        }
                    }
                }
            }
        }

        // weekly map or array-of-pairs
        if (!empty($pattern['weekly'])) {
            if (is_array($pattern['weekly'])) {
                $isAssoc = array_keys($pattern['weekly']) !== range(0, count($pattern['weekly']) - 1);
                if ($isAssoc) {
                    foreach ($pattern['weekly'] as $week => $shift) {
                        $weekNum = (int)$week;
                        if ($weekNum >= 1 && $weekNum <= 5 && is_string($shift) && $shift !== '') {
                            $norm['weekly'][] = ['week' => $weekNum, 'shift' => $shift];
                        }
                    }
                } else {
                    foreach ($pattern['weekly'] as $row) {
                        $week = isset($row['label']) ? (int)$row['label'] : (isset($row['week']) ? (int)$row['week'] : 0);
                        $shift = $row['value'] ?? ($row['shift'] ?? null);
                        if ($week >= 1 && $week <= 5 && is_string($shift) && $shift !== '') {
                            $norm['weekly'][] = ['week' => $week, 'shift' => $shift];
                        }
                    }
                }
            }
        }

        // monthly map or array-of-pairs
        if (!empty($pattern['monthly'])) {
            if (is_array($pattern['monthly'])) {
                $isAssoc = array_keys($pattern['monthly']) !== range(0, count($pattern['monthly']) - 1);
                if ($isAssoc) {
                    foreach ($pattern['monthly'] as $month => $shift) {
                        $monthNum = (int)$month;
                        if ($monthNum >= 1 && $monthNum <= 12 && is_string($shift) && $shift !== '') {
                            $norm['monthly'][] = ['month' => $monthNum, 'shift' => $shift];
                        }
                    }
                } else {
                    foreach ($pattern['monthly'] as $row) {
                        $month = isset($row['label']) ? (int)$row['label'] : (isset($row['month']) ? (int)$row['month'] : 0);
                        $shift = $row['value'] ?? ($row['shift'] ?? null);
                        if ($month >= 1 && $month <= 12 && is_string($shift) && $shift !== '') {
                            $norm['monthly'][] = ['month' => $month, 'shift' => $shift];
                        }
                    }
                }
            }
        }

        // range
        if (!empty($pattern['range']) && is_array($pattern['range'])) {
            foreach ($pattern['range'] as $r) {
                $start = $r['start_date'] ?? null;
                $end = $r['end_date'] ?? null;
                $shift = $r['shift_id'] ?? ($r['shift'] ?? null);
                if (is_string($start) && is_string($end) && is_string($shift) && $shift !== '') {
                    $norm['range'][] = [
                        'start_date' => $start,
                        'end_date' => $end,
                        'shift_id' => $shift,
                    ];
                }
            }
        }

        // custom_day
        if (!empty($pattern['custom_day']) && is_array($pattern['custom_day'])) {
            foreach ($pattern['custom_day'] as $cd) {
                $on = isset($cd['on']) ? (string)$cd['on'] : '';
                $day = isset($cd['day']) ? strtolower((string)$cd['day']) : '';
                $shift = isset($cd['shift']) ? (string)$cd['shift'] : '';
                if ($on !== '' && $day !== '' && $shift !== '') {
                    $norm['custom_day'][] = ['on' => $on, 'day' => $day, 'shift' => $shift];
                }
            }
        }

        return $norm;
    }

    /** Determine if a mapping is in its assignment window (if window columns exist). */
    public static function isInAssignmentWindow(?string $startDateTime, ?string $endDateTime, ?Carbon $date = null): bool
    {
        $date = $date ?? Carbon::now();
        if ($startDateTime) {
            if ($date->lt(Carbon::parse($startDateTime))) return false;
        }
        if ($endDateTime) {
            if ($date->gt(Carbon::parse($endDateTime))) return false;
        }
        return true;
    }

    /** Get today's shifts for a schedule mapping, enforcing assignment window and pattern semantics. */
    public static function getTodayShiftsForScheduleMapping($pattern, ?string $mappingStartDateTime, ?string $mappingEndDateTime, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        if (!self::isInAssignmentWindow($mappingStartDateTime, $mappingEndDateTime, $date)) {
            return [];
        }

        $norm = self::normalizePattern($pattern);
        $today = [];

        // single: only on mapping start date (one day)
        if (!empty($norm['single']) && $mappingStartDateTime) {
            $startDay = Carbon::parse($mappingStartDateTime)->toDateString();
            if ($date->toDateString() === $startDay) {
                $today = array_merge($today, $norm['single']);
            }
        }

        // daily: compare today weekday
        if (!empty($norm['daily'])) {
            $todayDay = strtolower($date->format('l'));
            foreach ($norm['daily'] as $row) {
                if (($row['day'] ?? '') === $todayDay && !empty($row['shift'])) {
                    $today[] = $row['shift'];
                }
            }
        }

        // weekly: compare week-of-month 1..5
        if (!empty($norm['weekly'])) {
            $weekOfMonth = self::getWeekOfMonth($date);
            foreach ($norm['weekly'] as $row) {
                if (($row['week'] ?? 0) === $weekOfMonth && !empty($row['shift'])) {
                    $today[] = $row['shift'];
                }
            }
        }

        // monthly: compare month number 1..12
        if (!empty($norm['monthly'])) {
            $month = (int)$date->format('n');
            foreach ($norm['monthly'] as $row) {
                if (($row['month'] ?? 0) === $month && !empty($row['shift'])) {
                    $today[] = $row['shift'];
                }
            }
        }

        // range: if today within any range
        if (!empty($norm['range'])) {
            $todayStr = $date->toDateString();
            foreach ($norm['range'] as $r) {
                $start = Carbon::parse($r['start_date'])->toDateString();
                $end = Carbon::parse($r['end_date'])->toDateString();
                if ($todayStr >= $start && $todayStr <= $end && !empty($r['shift_id'])) {
                    $today[] = $r['shift_id'];
                }
            }
        }

        // custom_day: nth weekday
        if (!empty($norm['custom_day'])) {
            foreach ($norm['custom_day'] as $cd) {
                if (self::matchesCustomDay($date, (string)$cd['on'], (string)$cd['day']) && !empty($cd['shift'])) {
                    $today[] = $cd['shift'];
                }
            }
        }

        return array_values(array_unique(array_filter($today)));
    }

    /** Generic method retained for cases without assignment window. */
    public static function getTodayShifts($pattern, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        $norm = self::normalizePattern($pattern);
        $today = [];

        // single: (generic fallback) treat as everyday (legacy behavior)
        if (!empty($norm['single'])) {
            $today = array_merge($today, $norm['single']);
        }

        // daily
        if (!empty($norm['daily'])) {
            $todayDay = strtolower($date->format('l'));
            foreach ($norm['daily'] as $row) {
                if (($row['day'] ?? '') === $todayDay && !empty($row['shift'])) {
                    $today[] = $row['shift'];
                }
            }
        }

        // weekly
        if (!empty($norm['weekly'])) {
            $weekOfMonth = self::getWeekOfMonth($date);
            foreach ($norm['weekly'] as $row) {
                if (($row['week'] ?? 0) === $weekOfMonth && !empty($row['shift'])) {
                    $today[] = $row['shift'];
                }
            }
        }

        // monthly
        if (!empty($norm['monthly'])) {
            $month = (int)$date->format('n');
            foreach ($norm['monthly'] as $row) {
                if (($row['month'] ?? 0) === $month && !empty($row['shift'])) {
                    $today[] = $row['shift'];
                }
            }
        }

        // range
        if (!empty($norm['range'])) {
            $todayStr = $date->toDateString();
            foreach ($norm['range'] as $r) {
                $start = Carbon::parse($r['start_date'])->toDateString();
                $end = Carbon::parse($r['end_date'])->toDateString();
                if ($todayStr >= $start && $todayStr <= $end && !empty($r['shift_id'])) {
                    $today[] = $r['shift_id'];
                }
            }
        }

        // custom_day
        if (!empty($norm['custom_day'])) {
            foreach ($norm['custom_day'] as $cd) {
                if (self::matchesCustomDay($date, (string)$cd['on'], (string)$cd['day']) && !empty($cd['shift'])) {
                    $today[] = $cd['shift'];
                }
            }
        }

        return array_values(array_unique(array_filter($today)));
    }

    /** Get the week number of the month (1-5). */
    private static function getWeekOfMonth(Carbon $date): int
    {
        // Week-of-month calculation aligning with human expectation (first 1-7 => week 1, etc.)
        $firstOfMonth = $date->copy()->startOfMonth();
        $offset = (int)$firstOfMonth->format('N') - 1; // 0 (Mon) .. 6 (Sun)
        $week = (int)ceil(($date->day + $offset) / 7);
        return max(1, min(5, $week));
    }

    /** Check if a date matches custom day criteria (e.g., 2nd Saturday, Last Monday). */
    private static function matchesCustomDay(Carbon $date, string $position, string $dayOfWeek): bool
    {
        $targetDay = strtolower($dayOfWeek);
        if (strtolower($date->format('l')) !== $targetDay) return false;

        if ($position === 'last') {
            $last = $date->copy()->endOfMonth();
            while (strtolower($last->format('l')) !== $targetDay) {
                $last->subDay();
            }
            return $date->isSameDay($last);
        }

        $pos = (int)$position;
        if ($pos < 1 || $pos > 4) return false;

        $occurrence = 0;
        $cursor = $date->copy()->startOfMonth();
        while ($cursor->month === $date->month) {
            if (strtolower($cursor->format('l')) === $targetDay) {
                $occurrence++;
                if ($cursor->isSameDay($date)) {
                    return $occurrence === $pos;
                }
            }
            $cursor->addDay();
        }
        return false;
    }

    /**
     * Get detailed information about today's shifts from a schedule
     *
     * @param string|array $pattern
     * @param Carbon|null $date
     * @return array Array with pattern type and shift IDs
     */
    public static function getTodayShiftDetails($pattern, ?Carbon $date = null): array
    {
        if (is_string($pattern)) {
            $pattern = json_decode($pattern, true) ?? [];
        }

        if (!is_array($pattern)) {
            return [];
        }

        $date = $date ?? Carbon::now();
        $details = [];

        // Check each pattern type
        if (!empty($pattern['single']) && is_array($pattern['single'])) {
            $details['single'] = $pattern['single'];
        }

        if (!empty($pattern['daily']) && is_array($pattern['daily'])) {
            $todayDay = strtolower($date->format('l'));
            $dailyShifts = [];

            foreach ($pattern['daily'] as $dayShift) {
                if (
                    isset($dayShift['label'], $dayShift['value']) &&
                    strtolower($dayShift['label']) === $todayDay &&
                    !empty($dayShift['value'])
                ) {
                    $dailyShifts[] = $dayShift['value'];
                }
            }

            if (!empty($dailyShifts)) {
                $details['daily'] = $dailyShifts;
            }
        }

        // Add other pattern types as needed...

        return $details;
    }
}
