<?php
namespace App\Jobs;

use App\Facades\Database;
use App\Facades\Developer;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Job to process smart attendance records and transfer them to the main attendance table.
 */
class AttendanceProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $attendanceId;
    protected $businessId;
    protected $userAgent;

    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var AttendanceService
     */
    protected $attendanceService;

    /**
     * Create a new job instance.
     *
     * @param string $attendanceId The attendance_id from smart_attendance table
     * @param string $businessId The business_id for database connection
     * @param string|null $userAgent The user agent string from the request
     */
    public function __construct($attendanceId, $businessId, $userAgent = null)
    {
        $this->attendanceId = $attendanceId;
        $this->businessId = $businessId;
        $this->userAgent = $userAgent;
    }

    /**
     * Execute the job.
     *
     * @param AttendanceService $attendanceService
     * @return void
     */
    public function handle(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;

        try {
            Developer::alert('AttendanceProcess Job Started', [
                'attendance_id' => $this->attendanceId,
                'business_id' => $this->businessId,
                'user_agent' => $this->userAgent,
            ]);

            $connectionName = Database::getConnection($this->businessId);
            $this->conn = $connectionName;

            Developer::alert('AttendanceProcess Job Started', [
                'attendance_id' => $this->attendanceId,
                'business_id' => $this->businessId,
                'user_agent' => $this->userAgent,
                'connection_name' => $connectionName
            ]);

            $smartAttendance = $this->conn->table('smart_attendance')
                ->where('attendance_id', $this->attendanceId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->first();

            if (!$smartAttendance) {
                Developer::alert('Smart attendance record not found', ['attendance_id' => $this->attendanceId]);
                return;
            }

            $smartAttendance = (object) $smartAttendance;
            Developer::alert('Smart attendance record found', ['record' => $smartAttendance]);

            $user = $this->conn->table('users')
                ->where('user_id', $smartAttendance->user_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$user) {
                Developer::alert('User not found', ['user_id' => $smartAttendance->user_id]);
                return;
            }

            $user = (object) $user;
            Developer::alert('User data found', ['user' => $user]);

            $scope = $this->conn->table('scopes')
                ->where('scope_id', $user->scope_id)
                ->first();
            Developer::alert('scope data found', ['scope' => $scope]);

            $attendanceTime = Carbon::parse($smartAttendance->timestamp);
            $attendanceDate = $attendanceTime->toDateString();
            $attendanceTimeOnly = $attendanceTime->toTimeString();

            $attendanceData = [
                'attendance_id' => $smartAttendance->attendance_id,
                'user_id' => $smartAttendance->user_id,
                'scope_id' => $user->scope_id,
                'scope_json' => $scope ? json_encode($scope) : null,
                'employee_id' => $user->employee_id ?? $user->user_id,
                'name' => $user->name ?? $user->first_name . ' ' . $user->last_name,
                'device_id' => null,
                'device_info' => $this->userAgent,
                'd_user_id' => null,
                'method' => $this->mapMethod($smartAttendance->method),
                'attendance_time' => $attendanceTimeOnly,
                'attendance_date' => $attendanceDate,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $closestShift = null;
            $minTimeDiff = PHP_INT_MAX;
            $checkIn = null;
            $checkOut = null;
            $lateIn = null;
            $earlyOut = null;
            $overtimeIn = null;
            $overtimeOut = null;
            $workingHours = null;
            $overtime = null;
            $shiftIds = [];

            try {
                $shiftIds = $this->attendanceService->getShifts(
                    $this->businessId,
                    $smartAttendance->user_id,
                    $attendanceDate,
                    null
                );
                Developer::alert('Shifts retrieved successfully', ['shift_ids' => $shiftIds]);
            } catch (Exception $e) {
                Developer::alert('Failed to fetch shifts from shift_mapping', [
                    'user_id' => $smartAttendance->user_id,
                    'business_id' => $this->businessId,
                    'error' => $e->getMessage()
                ]);

                try {
                    $shiftIds = $this->conn->table('shifts')
                        ->where('is_active', 1)
                        ->whereNull('deleted_at')
                        ->pluck('shift_id')
                        ->toArray();
                    Developer::alert('Fallback: Retrieved shifts from shifts table', ['shift_ids' => $shiftIds]);
                } catch (Exception $fallbackE) {
                    Developer::alert('Failed to fetch shifts from shifts table', [
                        'user_id' => $smartAttendance->user_id,
                        'business_id' => $this->businessId,
                        'error' => $fallbackE->getMessage()
                    ]);
                }
            }

            // Check existing attendance record to determine overtime logic
            $existingRecord = $this->conn->table('attendance')
                ->where('user_id', $smartAttendance->user_id)
                ->where('attendance_date', $attendanceDate)
                ->where(function ($query) use ($shiftIds) {
                    if (!empty($shiftIds)) {
                        $query->whereIn('shift_id', $shiftIds);
                    } else {
                        $query->whereNull('shift_id');
                    }
                })
                ->first();

            if (!empty($shiftIds)) {
                foreach ($shiftIds as $shiftId) {
                    $shift = $this->conn->table('shifts')
                        ->where('shift_id', $shiftId)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at')
                        ->first();

                    if ($shift) {
                        $shift = (object) $shift;
                        Developer::alert('Shift data found', ['shift' => $shift]);

                        $startTime = Carbon::parse($shift->start_time);
                        $endTime = Carbon::parse($shift->end_time);
                        $gracePeriod = $shift->grace_period ?? 15;
                        $minOvertimeMinutes = $shift->min_overtime_minutes ?? 0;

                        $startWindowStart = $startTime->copy()->subMinutes($gracePeriod);
                        $startWindowEnd = $startTime->copy()->addMinutes($gracePeriod);
                        $endWindowStart = $endTime->copy()->subMinutes($gracePeriod);
                        $endWindowEnd = $endTime->copy()->addMinutes($gracePeriod);

                        // Check if the shift is overtime eligible and check_out exists
                        $isOvertimeEligible = $shift->overtime_eligible ?? 0;
                        $hasCheckOut = $existingRecord && $existingRecord->check_out;

                        if ($isOvertimeEligible && $hasCheckOut && $attendanceTime->gt($endTime)) {
                            $overtimeWindowEnd = $endTime->copy()->addMinutes($minOvertimeMinutes);
                            if (!$existingRecord->overtime_in) {
                                $overtimeIn = $attendanceTimeOnly;
                                $overtimeSeconds = $endTime->diffInSeconds($attendanceTime);
                                $overtime = gmdate('H:i:s', $overtimeSeconds);
                            } elseif ($existingRecord->overtime_in && !$existingRecord->overtime_out) {
                                $existingOvertimeIn = Carbon::parse($attendanceDate . ' ' . $existingRecord->overtime_in);
                                $overtimeWindowEnd = $existingOvertimeIn->copy()->addMinutes($minOvertimeMinutes);
                                if ($attendanceTime->lte($overtimeWindowEnd)) {
                                    // Within min_overtime_minutes, add to raw_punches
                                    $rawJson = [
                                        'attendance_id' => $smartAttendance->attendance_id,
                                        'timestamp' => $smartAttendance->timestamp,
                                        'method' => $this->mapMethod($smartAttendance->method),
                                        'user_agent' => $this->userAgent,
                                        'type' => 'raw_punch',
                                    ];
                                    $existingRawJson = json_decode($existingRecord->raw_json, true) ?? [];
                                    $existingRawJson[] = $rawJson;
                                    $this->conn->table('attendance')
                                        ->where('id', $existingRecord->id)
                                        ->update([
                                            'raw_json' => json_encode($existingRawJson),
                                            'updated_at' => now(),
                                        ]);
                                    Developer::alert('Scan added to raw_punches', [
                                        'attendance_id' => $this->attendanceId,
                                        'raw_punch' => $rawJson
                                    ]);
                                    return;
                                } else {
                                    $overtimeOut = $attendanceTimeOnly;
                                    $overtimeSeconds = $endTime->diffInSeconds($attendanceTime);
                                    $overtime = gmdate('H:i:s', $overtimeSeconds);
                                }
                            } else {
                                // After overtime_out, add to raw_punches
                                $rawJson = [
                                    'attendance_id' => $smartAttendance->attendance_id,
                                    'timestamp' => $smartAttendance->timestamp,
                                    'method' => $this->mapMethod($smartAttendance->method),
                                    'user_agent' => $this->userAgent,
                                    'type' => 'raw_punch',
                                ];
                                $existingRawJson = json_decode($existingRecord->raw_json, true) ?? [];
                                $existingRawJson[] = $rawJson;
                                $this->conn->table('attendance')
                                    ->where('id', $existingRecord->id)
                                    ->update([
                                        'raw_json' => json_encode($existingRawJson),
                                        'updated_at' => now(),
                                    ]);
                                Developer::alert('Scan added to raw_punches after overtime_out', [
                                    'attendance_id' => $this->attendanceId,
                                    'raw_punch' => $rawJson
                                ]);
                                return;
                            }
                        } else {
                            // Standard check-in/check-out logic
                            if ($attendanceTime->between($startWindowStart, $startWindowEnd)) {
                                $checkIn = $attendanceTimeOnly;
                                if ($attendanceTime->gt($startTime)) {
                                    $lateIn = $startTime->diff($attendanceTime)->format('%H:%I:%S');
                                }
                            } elseif ($attendanceTime->between($endWindowStart, $endWindowEnd)) {
                                $checkOut = $attendanceTimeOnly;
                                if ($attendanceTime->lt($endTime)) {
                                    $earlyOut = $attendanceTime->diff($endTime)->format('%H:%I:%S');
                                }
                            }
                        }

                        $startDiff = abs($attendanceTime->diffInSeconds($startTime));
                        $endDiff = abs($attendanceTime->diffInSeconds($endTime));
                        $currentMinDiff = min($startDiff, $endDiff);

                        if ($currentMinDiff < $minTimeDiff) {
                            $minTimeDiff = $currentMinDiff;
                            $closestShift = $shift;
                        }
                    }
                }
            } else {
                Developer::alert('No shifts available for processing', [
                    'user_id' => $smartAttendance->user_id,
                    'business_id' => $this->businessId,
                    'attendance_date' => $attendanceDate
                ]);
            }

            if (!$checkIn && !$checkOut && !$overtimeIn && !$overtimeOut && $closestShift) {
                $startTime = Carbon::parse($closestShift->start_time);
                $endTime = Carbon::parse($closestShift->end_time);
                $startDiff = abs($attendanceTime->diffInSeconds($startTime));
                $endDiff = abs($attendanceTime->diffInSeconds($endTime));
                $gracePeriod = $closestShift->grace_period ?? 15;
                $minOvertimeMinutes = $closestShift->min_overtime_minutes ?? 0;
                $isOvertimeEligible = $closestShift->overtime_eligible ?? 0;
                $hasCheckOut = $existingRecord && $existingRecord->check_out;

                if ($isOvertimeEligible && $hasCheckOut && $attendanceTime->gt($endTime)) {
                    $overtimeWindowEnd = $endTime->copy()->addMinutes($minOvertimeMinutes);
                    if (!$existingRecord->overtime_in) {
                        $overtimeIn = $attendanceTimeOnly;
                        $overtimeSeconds = $endTime->diffInSeconds($attendanceTime);
                        $overtime = gmdate('H:i:s', $overtimeSeconds);
                    } elseif ($existingRecord->overtime_in && !$existingRecord->overtime_out) {
                        $existingOvertimeIn = Carbon::parse($attendanceDate . ' ' . $existingRecord->overtime_in);
                        $overtimeWindowEnd = $existingOvertimeIn->copy()->addMinutes($minOvertimeMinutes);
                        if ($attendanceTime->lte($overtimeWindowEnd)) {
                            $rawJson = [
                                'attendance_id' => $smartAttendance->attendance_id,
                                'timestamp' => $smartAttendance->timestamp,
                                'method' => $this->mapMethod($smartAttendance->method),
                                'user_agent' => $this->userAgent,
                                'type' => 'raw_punch',
                            ];
                            $existingRawJson = json_decode($existingRecord->raw_json, true) ?? [];
                            $existingRawJson[] = $rawJson;
                            $this->conn->table('attendance')
                                ->where('id', $existingRecord->id)
                                ->update([
                                    'raw_json' => json_encode($existingRawJson),
                                    'updated_at' => now(),
                                ]);
                            Developer::alert('Scan added to raw_punches', [
                                'attendance_id' => $this->attendanceId,
                                'raw_punch' => $rawJson
                            ]);
                            return;
                        } else {
                            $overtimeOut = $attendanceTimeOnly;
                            $overtimeSeconds = $endTime->diffInSeconds($attendanceTime);
                            $overtime = gmdate('H:i:s', $overtimeSeconds);
                        }
                    } else {
                        $rawJson = [
                            'attendance_id' => $smartAttendance->attendance_id,
                            'timestamp' => $smartAttendance->timestamp,
                            'method' => $this->mapMethod($smartAttendance->method),
                            'user_agent' => $this->userAgent,
                            'type' => 'raw_punch',
                        ];
                        $existingRawJson = json_decode($existingRecord->raw_json, true) ?? [];
                        $existingRawJson[] = $rawJson;
                        $this->conn->table('attendance')
                            ->where('id', $existingRecord->id)
                            ->update([
                                'raw_json' => json_encode($existingRawJson),
                                'updated_at' => now(),
                            ]);
                        Developer::alert('Scan added to raw_punches after overtime_out', [
                            'attendance_id' => $this->attendanceId,
                            'raw_punch' => $rawJson
                        ]);
                        return;
                    }
                } else {
                    if ($startDiff <= $endDiff) {
                        $checkIn = $attendanceTimeOnly;
                        if ($attendanceTime->gt($startTime)) {
                            $lateIn = $startTime->diff($attendanceTime)->format('%H:%I:%S');
                        }
                    } else {
                        $checkOut = $attendanceTimeOnly;
                        if ($attendanceTime->lt($endTime)) {
                            $earlyOut = $attendanceTime->diff($endTime)->format('%H:%I:%S');
                        }
                    }
                }
            }

            if ($closestShift) {
                $attendanceData['shift_id'] = $closestShift->shift_id;
                $attendanceData['shift_json'] = json_encode([
                    'shift_id' => $closestShift->shift_id,
                    'name' => $closestShift->name,
                    'start_time' => $closestShift->start_time,
                    'end_time' => $closestShift->end_time,
                    'grace_period' => $closestShift->grace_period,
                    'break_duration_minutes' => $closestShift->break_duration_minutes,
                    'is_cross_day_shift' => $closestShift->is_cross_day_shift,
                    'min_work_hours' => $closestShift->min_work_hours,
                    'overtime_eligible' => $closestShift->overtime_eligible,
                    'overtime_rate_type' => $closestShift->overtime_rate_type,
                    'overtime_rate_value' => $closestShift->overtime_rate_value,
                    'min_overtime_minutes' => $closestShift->min_overtime_minutes ?? 0,
                ]);
            }

            $attendanceData['check_in'] = $checkIn;
            $attendanceData['late_in'] = $lateIn;
            $attendanceData['early_out'] = $earlyOut;
            $attendanceData['check_out'] = $checkOut;
            $attendanceData['overtime_in'] = $overtimeIn;
            $attendanceData['overtime_out'] = $overtimeOut;
            $attendanceData['overtime'] = $overtime;

            // Calculate working hours if both check_in and check_out exist
            if ($checkIn && $checkOut) {
                $checkInTime = Carbon::parse($attendanceDate . ' ' . $checkIn);
                $checkOutTime = Carbon::parse($attendanceDate . ' ' . $checkOut);
                $workingHoursSeconds = $checkInTime->diffInSeconds($checkOutTime);
                $workingHours = gmdate('H:i:s', $workingHoursSeconds);
                $attendanceData['working_hours'] = $workingHours;
            }

            $rawJson = [
                'attendance_id' => $smartAttendance->attendance_id,
                'timestamp' => $smartAttendance->timestamp,
                'check_in' => $checkIn,
                'late_in' => $lateIn,
                'check_out' => $checkOut,
                'early_out' => $earlyOut,
                'overtime_in' => $overtimeIn,
                'overtime_out' => $overtimeOut,
                'method' => $this->mapMethod($smartAttendance->method),
                'shift_status' => $closestShift ? 'assigned' : 'unassigned',
                'user_agent' => $this->userAgent,
            ];

            if ($existingRecord) {
                $existingRawJson = json_decode($existingRecord->raw_json, true) ?? [];
                $existingRawJson[] = $rawJson;
                $updateData = [
                    'raw_json' => json_encode($existingRawJson),
                    'device_info' => $this->userAgent,
                    'updated_at' => now(),
                ];

                if ($checkIn && !$existingRecord->check_in) {
                    $updateData['check_in'] = $checkIn;
                    $updateData['late_in'] = $lateIn;
                }
                if ($checkOut && !$existingRecord->check_out) {
                    $updateData['check_out'] = $checkOut;
                    $updateData['early_out'] = $earlyOut;
                    // Calculate working hours
                    if ($existingRecord->check_in || $checkIn) {
                        $effectiveCheckIn = $checkIn ?: $existingRecord->check_in;
                        $checkInTime = Carbon::parse($attendanceDate . ' ' . $effectiveCheckIn);
                        $checkOutTime = Carbon::parse($attendanceDate . ' ' . $checkOut);
                        $workingHoursSeconds = $checkInTime->diffInSeconds($checkOutTime);
                        $workingHours = gmdate('H:i:s', $workingHoursSeconds);
                        $updateData['working_hours'] = $workingHours;
                    }
                }
                if ($overtimeIn && !$existingRecord->overtime_in) {
                    $updateData['overtime_in'] = $overtimeIn;
                    $updateData['overtime'] = $overtime;
                }
                if ($overtimeOut && !$existingRecord->overtime_out) {
                    $updateData['overtime_out'] = $overtimeOut;
                    $updateData['overtime'] = $overtime;
                }
                if ($closestShift && !$existingRecord->shift_id) {
                    $updateData['shift_id'] = $closestShift->shift_id;
                    $updateData['shift_json'] = $attendanceData['shift_json'];
                }

                $this->conn->table('attendance')
                    ->where('id', $existingRecord->id)
                    ->update($updateData);

                Developer::alert('Existing attendance record updated', [
                    'attendance_id' => $this->attendanceId,
                    'user_id' => $smartAttendance->user_id,
                    'attendance_date' => $attendanceDate,
                    'update_data' => $updateData
                ]);
            } else {
                $attendanceData['raw_json'] = json_encode([$rawJson]);
                $this->conn->table('attendance')->insert($attendanceData);

                Developer::alert('New attendance record inserted', [
                    'attendance_id' => $this->attendanceId,
                    'user_id' => $smartAttendance->user_id,
                    'attendance_date' => $attendanceDate,
                    'attendance_data' => $attendanceData
                ]);
            }

            Developer::alert('Attendance record processed successfully', [
                'attendance_id' => $this->attendanceId,
                'result' => 'success',
                'attendance_data' => $attendanceData
            ]);

        } catch (Exception $e) {
            Developer::alert('AttendanceProcess Job Error', [
                'attendance_id' => $this->attendanceId,
                'business_id' => $this->businessId,
                'user_agent' => $this->userAgent,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Map method from smart_attendance to attendance table format
     *
     * @param string $method
     * @return string
     */
    private function mapMethod($method)
    {
        switch ($method) {
            case 'geo':
                return 'geo';
            case 'face':
                return 'face';
            case 'qr':
                return 'qr';
            case 'geo-face':
                return 'geo-face';
            case 'geo-qr':
                return 'geo-qr';
            default:
                return 'unknown';
        }
    }
}