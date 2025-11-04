<?php
namespace App\Services;

use App\Facades\Database;
use App\Facades\Developer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Get shifts for a user within a given date or range, sorted by start_date_time, with business-specific caching.
     *
     * @param string $businessId The business_id for database connection
     * @param string $userId
     * @param string $startDate  // format: 'Y-m-d H:i:s' or 'Y-m-d'
     * @param string|null $endDate // nullable
     * @param array $scheduleIds // optional, if provided, only fetch shifts from these specific schedule_ids
     * @return array
     * @throws Exception
     */
    public function getShifts(string $businessId, string $userId, string $startDate, ?string $endDate = null, array $scheduleIds = []): array
    {
        try {
            // Generate cache key for business-specific caching
            $cacheKey = 'shifts_' . $businessId;
            $cacheTTL = 3600; // Cache for 1 hour (3600 seconds)

            // Attempt to retrieve from cache
            // $cachedData = Cache::get($cacheKey);
            // if ($cachedData !== null) {
            //     // Filter cached data for the specific user
            //     $userShifts = array_filter($cachedData, fn($shift) => $shift['user_id'] === $userId);
            //     $userShifts = array_values($userShifts); // Reindex array
            //     Developer::alert('Shifts retrieved from cache for user', [
            //         'business_id' => $businessId,
            //         'user_id' => $userId,
            //         'cache_key' => $cacheKey,
            //         'shift_count' => count($userShifts)
            //     ]);
            //     return $userShifts;
            // }

            // Set up the business-specific database connection
            $connectionName = Database::getConnection($businessId);
            $conn = DB::connection($connectionName);

            Developer::alert('In AttendanceService get_shifts', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'schedule_ids' => $scheduleIds
            ]);

            $requested_start = Carbon::parse($startDate);
            $requested_end = $endDate ? Carbon::parse($endDate) : $requested_start->copy()->endOfDay();

            // Query all relevant shift mappings for the business
            $query = $conn->table('shift_mapping');
            if (!empty($scheduleIds)) {
                $query->where('type', 'schedule')->whereIn('ref_id', $scheduleIds);
            }

            if ($endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date_time', [$startDate, $endDate])
                      ->orWhereBetween('end_date_time', [$startDate, $endDate])
                      ->orWhere(function ($sub) use ($startDate, $endDate) {
                          $sub->where('start_date_time', '<=', $startDate)
                              ->where('end_date_time', '>=', $endDate);
                      });
                });
            } else {
                $query->where('start_date_time', '<=', $startDate)
                      ->where('end_date_time', '>=', $startDate);
            }

            $mappings = $query->get();

            $shiftAssignments = []; // Structure: [shift_id => [user_id, effective_start, effective_end, shift_data]]
            $shiftIds = [];

            foreach ($mappings as $mapping) {
                $mapping_start = Carbon::parse($mapping->start_date_time);
                $mapping_end = Carbon::parse($mapping->end_date_time);

                $effective_start = $requested_start->max($mapping_start);
                $effective_end = $requested_end->min($mapping_end);

                if ($effective_start > $effective_end) {
                    continue;
                }

                if ($mapping->type === 'shift') {
                    $shiftIds[] = $mapping->ref_id;
                    $shiftAssignments[$mapping->ref_id][] = [
                        'user_id' => $mapping->user_id,
                        'effective_start' => $effective_start,
                        'effective_end' => $effective_end
                    ];
                } elseif ($mapping->type === 'schedule') {
                    $schedule = $conn->table('shift_schedules')->where('schedule_id', $mapping->ref_id)->first();
                    if ($schedule) {
                        $pattern = json_decode($schedule->pattern, true) ?? [];

                        // single: include if effective period exists
                        if (isset($pattern['single']) && is_array($pattern['single'])) {
                            foreach ($pattern['single'] as $sid) {
                                if (is_string($sid)) {
                                    $shiftAssignments[$sid][] = [
                                        'user_id' => $mapping->user_id,
                                        'effective_start' => $effective_start,
                                        'effective_end' => $effective_end
                                    ];
                                    $shiftIds[] = $sid;
                                }
                            }
                        }

                        // daily
                        if (isset($pattern['daily']) && is_array($pattern['daily'])) {
                            foreach ($pattern['daily'] as $day => $sid) {
                                $dayCapital = ucfirst($day);
                                $next = $effective_start->copy()->next($dayCapital);
                                while ($next <= $effective_end) {
                                    $shiftAssignments[$sid][] = [
                                        'user_id' => $mapping->user_id,
                                        'effective_start' => $next->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $sid)->value('start_time') ?? '00:00:00'),
                                        'effective_end' => $next->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $sid)->value('end_time') ?? '23:59:59')
                                    ];
                                    $shiftIds[] = $sid;
                                    $next->addWeek();
                                }
                            }
                        }

                        // weekly
                        if (isset($pattern['weekly']) && is_array($pattern['weekly'])) {
                            foreach ($pattern['weekly'] as $dow_str => $sid) {
                                $dow = (int) $dow_str;
                                if ($dow < 1 || $dow > 7) continue;
                                $next = $effective_start->copy()->next($dow);
                                while ($next <= $effective_end) {
                                    $shiftAssignments[$sid][] = [
                                        'user_id' => $mapping->user_id,
                                        'effective_start' => $next->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $sid)->value('start_time') ?? '00:00:00'),
                                        'effective_end' => $next->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $sid)->value('end_time') ?? '23:59:59')
                                    ];
                                    $shiftIds[] = $sid;
                                    $next->addWeek();
                                }
                            }
                        }

                        // monthly
                        if (isset($pattern['monthly']) && is_array($pattern['monthly'])) {
                            foreach ($pattern['monthly'] as $mday_str => $sid) {
                                $mday = (int) $mday_str;
                                if ($mday < 1 || $mday > 31) continue;
                                $current = $effective_start->copy()->startOfMonth();
                                while ($current <= $effective_end) {
                                    $target = $current->copy()->setDay($mday);
                                    if ($target->month === $current->month && $target >= $effective_start && $target <= $effective_end) {
                                        $shiftAssignments[$sid][] = [
                                            'user_id' => $mapping->user_id,
                                            'effective_start' => $target->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $sid)->value('start_time') ?? '00:00:00'),
                                            'effective_end' => $target->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $sid)->value('end_time') ?? '23:59:59')
                                        ];
                                        $shiftIds[] = $sid;
                                    }
                                    $current->addMonthNoOverflow();
                                }
                            }
                        }

                        // range
                        if (isset($pattern['range']) && is_array($pattern['range'])) {
                            foreach ($pattern['range'] as $r) {
                                if (!isset($r['start_date'], $r['end_date'], $r['shift_id'])) continue;
                                $rule_start = Carbon::parse($r['start_date']);
                                $rule_end = Carbon::parse($r['end_date']);
                                $rule_effective_start = $rule_start->max($effective_start);
                                $rule_effective_end = $rule_end->min($effective_end);
                                if ($rule_effective_start <= $rule_effective_end) {
                                    $shiftAssignments[$r['shift_id']][] = [
                                        'user_id' => $mapping->user_id,
                                        'effective_start' => $rule_effective_start->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $r['shift_id'])->value('start_time') ?? '00:00:00'),
                                        'effective_end' => $rule_effective_end->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $r['shift_id'])->value('end_time') ?? '23:59:59')
                                    ];
                                    $shiftIds[] = $r['shift_id'];
                                }
                            }
                        }

                        // custom_day
                        if (isset($pattern['custom_day']) && is_array($pattern['custom_day'])) {
                            foreach ($pattern['custom_day'] as $c) {
                                if (!isset($c['on'], $c['day'], $c['shift'])) continue;
                                $on = (int) $c['on'];
                                $day = strtolower($c['day']);
                                $dayCapital = ucfirst($day);
                                if ($on < 1 || $on > 5) continue;
                                $current = $effective_start->copy()->startOfMonth();
                                while ($current <= $effective_end) {
                                    $month_first = $current->copy();
                                    $first_occurrence = $month_first->next($dayCapital);
                                    if ($first_occurrence->month !== $current->month) {
                                        $current->addMonthNoOverflow();
                                        continue;
                                    }
                                    $nth_occurrence = $first_occurrence->copy()->addWeeks($on - 1);
                                    if ($nth_occurrence->month === $current->month && $nth_occurrence >= $effective_start && $nth_occurrence <= $effective_end) {
                                        $shiftAssignments[$c['shift']][] = [
                                            'user_id' => $mapping->user_id,
                                            'effective_start' => $nth_occurrence->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $c['shift'])->value('start_time') ?? '00:00:00'),
                                            'effective_end' => $nth_occurrence->copy()->setTimeFromTimeString($conn->table('shifts')->where('shift_id', $c['shift'])->value('end_time') ?? '23:59:59')
                                        ];
                                        $shiftIds[] = $c['shift'];
                                    }
                                    $current->addMonthNoOverflow();
                                }
                            }
                        }
                    }
                }
            }

            $shiftIds = array_unique($shiftIds);

            $shifts = $conn->table('shifts')
                ->whereIn('shift_id', $shiftIds)
                ->get()
                ->keyBy('shift_id');

            $result = [];
            foreach ($shiftAssignments as $shiftId => $assignments) {
                if (!isset($shifts[$shiftId])) {
                    continue;
                }
                foreach ($assignments as $assignment) {
                    $shiftData = (array) $shifts[$shiftId];
                    $shiftData['user_id'] = $assignment['user_id'];
                    $shiftData['effective_start_time'] = $assignment['effective_start']->toDateTimeString();
                    $shiftData['effective_end_time'] = $assignment['effective_end']->toDateTimeString();
                    $result[] = $shiftData;
                }
            }

            // Sort by effective_start_time
            usort($result, function ($a, $b) {
                return strcmp($a['effective_start_time'], $b['effective_start_time']);
            });

            // Store in cache
            Cache::put($cacheKey, $result, $cacheTTL);

            Developer::alert('Shifts retrieved from shift_mapping and cached', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'shift_ids' => $shiftIds,
                'cache_key' => $cacheKey
            ]);

            // Filter for the specific user
            $userShifts = array_filter($result, fn($shift) => $shift['user_id'] === $userId);
            $userShifts = array_values($userShifts); // Reindex array

            return $userShifts;
        } catch (Exception $e) {
            Developer::alert('Error in get_shifts', [
                'business_id' => $businessId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Error fetching shifts: " . $e->getMessage());
        }
    }
}