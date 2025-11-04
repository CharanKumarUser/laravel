{{-- Template: My Daily Attendance Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'My Daily Attendance')
@push('styles') {{-- Header Styles--}} @endpush
@push('pre-scripts') {{-- Header Scripts--}} @endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('shift-slider');
    if (!slider) return;
    const prev = document.getElementById('shift-prev');
    const next = document.getElementById('shift-next');
    const step = 280;
    function scrollBy(dx) { slider.scrollBy({ left: dx, behavior: 'smooth' }); }
    prev && prev.addEventListener('click', () => scrollBy(-step));
    next && next.addEventListener('click', () => scrollBy(step));
});
</script>
@endpush
@section('content')
		
			<div class="content">

				<!-- Breadcrumb -->
				<div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
					<div class="my-auto mb-2">
						<h2 class="mb-1">Employee Attendance</h2>
						<nav>
							<ol class="breadcrumb mb-0">
								<li class="breadcrumb-item">
									<a href="index.html"><i class="ti ti-smart-home"></i></a>
								</li>
								<li class="breadcrumb-item">
									Employee
								</li>
								<li class="breadcrumb-item active" aria-current="page">Employee Attendance</li>
							</ol>
						</nav>
					</div>
					<div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">
						<div class="me-2 mb-2">
							<div class="d-flex align-items-center border bg-white rounded p-1 me-2 icon-list">
								<a href="attendance-admin.html" class="btn btn-icon btn-sm active bg-primary text-white me-1"><i class="ti ti-brand-days-counter"></i></a>
								<a href="attendance-admin.html" class="btn btn-icon btn-sm"><i class="ti ti-calendar-event"></i></a>
							</div>
						</div>
						<div class="me-2 mb-2">
							<div class="dropdown">
								<a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
									<i class="ti ti-file-export me-1"></i>Export
								</a>
								<ul class="dropdown-menu  dropdown-menu-end p-3">
									<li>
										<a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
									</li>
									<li>
										<a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
									</li>
								</ul>
							</div>
						</div>
						<div class="mb-2">
							<a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#attendance_report"><i class="ti ti-file-analytics me-2"></i>Report</a>
						</div>
						<div class="ms-2 head-icons">
							<a href="javascript:void(0);" class="" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header">
								<i class="ti ti-chevrons-up"></i>
							</a>
						</div>
					</div>
				</div>
				<!-- /Breadcrumb -->

				<div class="row">
					<div class="col-xl-3 col-lg-4 d-flex">
						<div class="card flex-fill">
							<div class="card-body">
							<div class="mb-3 text-center">
								<h6 class="fw-medium text-gray-5 mb-2">Good {{ now()->format('A') === 'AM' ? 'Morning' : 'Evening' }}, {{ $data['kpis']['greeting_name'] ?? 'User' }}</h6>
								<h4>{{ $data['kpis']['now'] ?? now()->format('h:i A, d M Y') }}</h4>
								
								@if(!empty($data['user_shifts']))
									<div class="mt-3">
										<h6 class="fw-medium text-primary mb-2">Today's Shifts ({{ $data['shift_count'] }})</h6>
										@foreach($data['user_shifts'] as $shift)
											<div class="badge badge-light-primary me-1 mb-1">
												<i class="ti ti-clock me-1"></i>
												{{ $shift['name'] }}: {{ $shift['start_time_formatted'] }} - {{ $shift['end_time_formatted'] }}
											</div>
										@endforeach
									</div>
								@else
									<div class="mt-3">
										<div class="badge badge-warning">
											<i class="ti ti-alert-circle me-1"></i>No shifts assigned for today
										</div>
									</div>
								@endif
							</div>
									{{-- Circular avatar progress based on active shift --}}
									@php $pct = (float)($data['circle_progress_percent'] ?? 0); @endphp
									<div class="position-relative d-block mx-auto mb-3" style="width: 140px; height: 140px;">
										<svg viewBox="0 0 140 140" class="position-absolute top-0 start-0" style="width:140px; height:140px;">
											<circle cx="70" cy="70" r="60" stroke="rgba(255,255,255,0.15)" stroke-width="10" fill="none"/>
											@php
												$radius = 60; $circ = 2 * M_PI * $radius;
												$dash = ($pct/100) * $circ; $gap = $circ - $dash;
											@endphp
											<circle cx="70" cy="70" r="60" stroke="#22c55e" stroke-width="10" fill="none"
												stroke-dasharray="{{ $dash }} {{ $gap }}" stroke-linecap="round"
												transform="rotate(-90 70 70)"/>
										</svg>
										<div class="avatar avatar-xxl avatar-rounded position-absolute top-50 start-50 translate-middle" style="width:110px; height:110px; overflow:hidden;">
											<img src="{{ $data['profile_image_url'] ?? asset('default/preview-profile.svg') }}" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
										</div>
									</div>
								<div class="text-center">
									@php
										// Calculate dynamic working hours for profile display
										$dynamicWorkingHours = $data['kpis']['today_working'] ?? '00:00:00';
										$todayRows = collect($data['attendance'] ?? [])->filter(fn($r) => ($r->attendance_date ?? '') === now()->toDateString());
										$currentlyWorking = $todayRows->filter(fn($r) => $r->check_in && !$r->check_out)->first();
										
										if ($currentlyWorking) {
											$checkInTime = \Carbon\Carbon::createFromFormat('H:i:s', $currentlyWorking->check_in);
											$currentTime = now();
											$dynamicWorkingHours = $checkInTime->diff($currentTime)->format('%H:%I:%S');
										}
										
										// Format for display
										$dynamicWorkingHM = function ($timeString) {
											if (empty($timeString)) return '-';
											try {
												[$h,$m] = array_map('intval', explode(':', $timeString));
												return sprintf('%02dh %02dm', $h, $m);
											} catch (\Throwable $e) { return $timeString; }
										};
									@endphp
									<div class="badge badge-md badge-primary mb-3">Production : {{ $dynamicWorkingHM($dynamicWorkingHours) }}</div>
									<h6 class="fw-medium d-flex align-items-center justify-content-center mb-3">
										<i class="ti ti-fingerprint text-primary me-1"></i>
									Punch In at  {{ $data['kpis']['punch_in_display'] ?? '-' }}
									@if(!empty($data['kpis']['punch_in_method']))
										<span class="ms-2 text-muted">(via {{ strtoupper($data['kpis']['punch_in_method']) }})</span>
									@endif
									</h6>

										{{-- Fill empty space: Active shift meta + mini stats --}}
										@if(!empty($data['active_shift_name']))
											<div class="mb-2">
												<span class="badge bg-light text-dark">
													<i class="ti ti-calendar-time me-1"></i>
													{{ $data['active_shift_name'] }}
													@if(!empty($data['active_shift_start']) && !empty($data['active_shift_end']))
														<span class="ms-1">({{ $data['active_shift_start'] }} - {{ $data['active_shift_end'] }})</span>
													@endif
												</span>
											</div>
										@endif

										@php
											$todayWorking = $data['kpis']['today_working'] ?? '00:00:00';
											$todayBreak = $data['kpis']['today_break'] ?? '0';
											$todayOver = $data['kpis']['today_overtime'] ?? '00:00:00';
										@endphp
										<div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
											<span class="badge bg-success-subtle text-success"><i class="ti ti-clock-hour-9 me-1"></i>{{ $dynamicWorkingHM($dynamicWorkingHours) }}</span>
											<span class="badge bg-info-subtle text-info"><i class="ti ti-bolt me-1"></i>{{ $data['kpis']['today_overtime_hm'] ?? '-' }}</span>
										</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-9 col-lg-8 d-flex">
						<div class="row flex-fill">
							<div class="col-xl-3 col-md-6">
								<div class="card">
									<div class="card-body">
										<div class="border-bottom mb-2 pb-2">
											<span class="avatar avatar-sm bg-primary mb-2"><i class="ti ti-clock-stop"></i></span>
									<h2 class="mb-2">{{ $dynamicWorkingHM($dynamicWorkingHours) }}</h2>
									<p class="fw-medium text-truncate">Total Hours Today</p>
										</div>
										
									</div>
								</div>
							</div>
							<div class="col-xl-3 col-md-6">
								<div class="card">
									<div class="card-body">
										<div class="border-bottom mb-2 pb-2">
											<span class="avatar avatar-sm bg-dark mb-2"><i class="ti ti-clock-up"></i></span>
									<h2 class="mb-2">{{ $data['kpis']['week_working_hm'] ?? '00h 00m' }}</h2>
											<p class="fw-medium text-truncate">Total Hours Week</p>
										</div>
										
									</div>
								</div>
							</div>
							<div class="col-xl-3 col-md-6">
								<div class="card">
									<div class="card-body">
										<div class="border-bottom mb-2 pb-2">
											<span class="avatar avatar-sm bg-info mb-2"><i class="ti ti-calendar-up"></i></span>
									<h2 class="mb-2">{{ $data['kpis']['month_working_hm'] ?? '00h 00m' }}</h2>
											<p class="fw-medium text-truncate">Total Hours Month</p>
										</div>
										
									</div>
								</div>
							</div>
							<div class="col-xl-3 col-md-6">
								<div class="card">
									<div class="card-body">
										<div class="border-bottom mb-2 pb-2">
											<span class="avatar avatar-sm bg-pink mb-2"><i class="ti ti-calendar-star"></i></span>
									<h2 class="mb-2">{{ $data['kpis']['month_overtime_hm'] ?? '00h 00m' }}</h2>
											<p class="fw-medium text-truncate">Overtime this Month</p>
										</div>
										
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<div class="card">
									<div class="card-body">
										@if(!empty($data['user_shifts']))
											<div class="row mb-4 align-items-center">
												<div class="col-12">
													<h6 class="fw-medium text-primary mb-3 d-flex align-items-center justify-content-between">
														<span><i class="ti ti-calendar-clock me-1"></i>Today's Shift Summary</span>
														<span>
															<button type="button" class="btn btn-sm btn-white me-1" id="shift-prev"><i class="ti ti-chevron-left"></i></button>
															<button type="button" class="btn btn-sm btn-white" id="shift-next"><i class="ti ti-chevron-right"></i></button>
														</span>
													</h6>
													<div class="overflow-auto" style="white-space: nowrap;">
														<div class="d-inline-flex" id="shift-slider" style="gap:12px;">
															@foreach($data['user_shifts'] as $shift)
																<div class="card border border-primary" style="min-width:260px; display:inline-block;">
																	<div class="card-body p-3">
																		<h6 class="card-title text-primary mb-2">{{ $shift['name'] }}</h6>
																		<p class="card-text mb-0">
																			<small class="text-muted"><i class="ti ti-clock me-1"></i>{{ $shift['start_time_formatted'] }} - {{ $shift['end_time_formatted'] }}</small>
																		</p>
																	</div>
																</div>
															@endforeach
														</div>
													</div>
												</div>
											</div>
										@endif
										
										<div class="row">
											<div class="col-xl-3">
												<div class="mb-3">
													<p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-primary me-1"></i>Total Working hours</p>
									<h3>{{ $dynamicWorkingHM($dynamicWorkingHours) }}</h3>
												</div>
											</div>
											<div class="col-xl-3">
												<div class="mb-3">
													<p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-success me-1"></i>Productive Hours</p>
									<h3>{{ $dynamicWorkingHM($dynamicWorkingHours) }}</h3>
												</div>
											</div>
											<div class="col-xl-3">
												<div class="mb-3">
													<p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-info me-1"></i>Overtime</p>
									<h3>{{ $data['kpis']['today_overtime_hm'] ?? '-' }}</h3>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
											@php
												$todayStr = now()->toDateString();
												function timeToPercentFullDay($t) {
													if (empty($t)) return null;
													$sec = \Carbon\Carbon::createFromFormat('H:i:s', $t)->secondsSinceMidnight();
													return ($sec / 86400) * 100;
												}
											@endphp
											{{-- Timeline for today's shifts --}}
											@php
												$todayRows = collect($data['attendance'] ?? [])->filter(fn($r) => ($r->attendance_date ?? '') === $todayStr);
												$byShift = $todayRows->groupBy(fn($r) => $r->shift_id ?? 'no-shift');
											@endphp
											@php
												$hasCheckIns = $byShift->filter(function($rows) {
													return $rows->filter(fn($r) => $r->check_in)->isNotEmpty();
												})->isNotEmpty();
											@endphp
											@if($hasCheckIns)
												{{-- One lane per assigned shift for today --}}
												@foreach($byShift as $shiftId => $rows)
													@php 
														$shiftName = $rows->first()->shift_name ?? 'Shift';
														$hasCheckIn = $rows->filter(fn($r) => $r->check_in)->isNotEmpty();
													@endphp
													@if($hasCheckIn)
														<div class="mb-2">
															<div class="d-flex align-items-center mb-1">
																<span class="badge bg-light text-dark me-2">{{ $shiftName }}</span>
															</div>
															<div class="position-relative w-100 bg-transparent-dark rounded" style="height: 28px; overflow: hidden;">
																@foreach($rows as $trow)
																	@php
																		$cinPct = isset($trow->check_in) ? timeToPercentFullDay($trow->check_in) : null;
																		$coutPct = isset($trow->check_out) ? timeToPercentFullDay($trow->check_out) : null;
																		$oinPct = isset($trow->overtime_in) ? timeToPercentFullDay($trow->overtime_in) : null;
																		$ooutPct = isset($trow->overtime_out) ? timeToPercentFullDay($trow->overtime_out) : null;
																	@endphp
																	@if(!is_null($cinPct) && !is_null($coutPct) && $coutPct > $cinPct)
																		<div class="position-absolute bg-success rounded" title="Working: {{ $trow->working_hours ?? '-' }}"
																			style="left: {{ $cinPct }}%; width: {{ $coutPct - $cinPct }}%; top: 4px; height: 20px;"></div>
																	@elseif(!is_null($cinPct) && is_null($coutPct))
																		{{-- Currently working - show progress from check-in to current time --}}
																		@php
																			$now = now();
																			$currentTime = $now->format('H:i:s');
																			$currentPct = timeToPercentFullDay($currentTime);
																			$workingWidth = $currentPct - $cinPct;
																		@endphp
																		<div class="position-absolute bg-success rounded" title="Currently Working"
																			style="left: {{ $cinPct }}%; width: {{ max(1, $workingWidth) }}%; top: 4px; height: 20px;"></div>
																	@endif
																	@if(!is_null($oinPct) && !is_null($ooutPct) && $ooutPct > $oinPct)
																		<div class="position-absolute bg-info rounded" title="Overtime: {{ $trow->overtime ?? '-' }}"
																			style="left: {{ $oinPct }}%; width: {{ $ooutPct - $oinPct }}%; top: 4px; height: 20px;"></div>
																	@endif
																	@if(!is_null($cinPct))
																		<div class="position-absolute" style="left: {{ $cinPct }}%; top: -6px; transform: translateX(-50%);">
																			<span class="badge bg-success">IN</span>
																		</div>
																	@endif
																	@if(!is_null($coutPct))
																		<div class="position-absolute" style="left: {{ $coutPct }}%; top: -6px; transform: translateX(-50%);">
																			<span class="badge bg-secondary">OUT</span>
																		</div>
																	@endif
																@endforeach
															</div>
														</div>
													@endif
												@endforeach
												
												{{-- Hour labels under timeline --}}
												<div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2 mt-2">
													@for($h=0; $h<=24; $h+=3)
														<span class="fs-10">{{ sprintf('%02d:00', $h % 24) }}</span>
													@endfor
												</div>
											@else
												<div class="text-center text-muted py-4">
													<i class="ti ti-clock-off fs-48 mb-2"></i>
													<p>No check-ins recorded for today</p>
												</div>
											@endif
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between">
						<h5 class="mb-0">Today's Attendance</h5>
					</div>
					<div class="card-body p-0">
						<div class="custom-datatable-filter table-responsive">
							<table class="table datatable">
								<thead class="thead-light">
									<tr>
										<th>Date</th>
										<th>Shift</th>
										<th>Check In</th>
										<th>Status</th>
										<th>Check Out</th>
										<th>Late</th>
										<th>Overtime</th>
										<th>Production Hours</th>
									</tr>
								</thead>
								<tbody>
						@php $todayOnly = collect($data['attendance'] ?? [])->filter(fn($r) => ($r->attendance_date ?? '') === now()->toDateString()); @endphp
						@forelse($todayOnly as $row)
							<tr>
								<td>
									{{ $row->attendance_date ? \Carbon\Carbon::parse($row->attendance_date)->format('d M Y') : '-' }}
								</td>
								<td>
									@if($row->shift_name && $row->shift_name !== 'No Shift Assigned')
										<div class="d-flex flex-column">
											<span class="badge badge-light-primary mb-1">{{ $row->shift_name }}</span>
											@if($row->shift_start && $row->shift_end)
												<small class="text-muted">
													{{ \Carbon\Carbon::createFromFormat('H:i:s', $row->shift_start)->format('h:i A') }} - 
													{{ \Carbon\Carbon::createFromFormat('H:i:s', $row->shift_end)->format('h:i A') }}
												</small>
											@endif
										</div>
									@else
										<span class="badge badge-warning">No Shift</span>
									@endif
								</td>
								<td>
									{{ $row->check_in ? \Carbon\Carbon::createFromFormat('H:i:s', $row->check_in)->format('h:i A') : '-' }}
								</td>
								<td>
									@php
										$now = now();
										$shiftStart = \Carbon\Carbon::createFromFormat('H:i:s', $row->shift_start ?? '08:00:00');
										$shiftEnd = \Carbon\Carbon::createFromFormat('H:i:s', $row->shift_end ?? '16:00:00');
										$gracePeriod = 10; // 10 minutes grace period
										$graceEnd = $shiftStart->copy()->addMinutes($gracePeriod);
										
										$status = 'not-started';
										$badgeClass = 'badge-secondary-transparent';
										$statusText = 'Not Started';
										
										if ($row->check_in && $row->check_out) {
											$status = 'completed';
											$badgeClass = 'badge-success-transparent';
											$statusText = 'Completed';
										} elseif ($row->check_in && !$row->check_out) {
											$status = 'present';
											$badgeClass = 'badge-success-transparent';
											$statusText = 'Present';
										} elseif ($now->greaterThan($graceEnd)) {
											$status = 'absent';
											$badgeClass = 'badge-danger-transparent';
											$statusText = 'Absent';
										}
									@endphp
									<span class="badge {{ $badgeClass }} d-inline-flex align-items-center">
										<i class="ti ti-point-filled me-1"></i>{{ $statusText }}
									</span>
								</td>
								<td>
									{{ $row->check_out ? \Carbon\Carbon::createFromFormat('H:i:s', $row->check_out)->format('h:i A') : '-' }}
								</td>
								<td>{{ $row->late_in ?? '-' }}</td>
								<td>
									{{ $row->overtime ?? '-' }}
								</td>
								<td>
									@php
										$workingHours = $row->working_hours ?? null;
										if (!$workingHours && $row->check_in && !$row->check_out) {
											// Calculate current working hours
											$checkInTime = \Carbon\Carbon::createFromFormat('H:i:s', $row->check_in);
											$currentTime = now();
											$workingHours = $checkInTime->diff($currentTime)->format('%H:%I:%S');
										}
									@endphp
									<span class="badge badge-{{ ($workingHours ?? '') >= '08:00:00' ? 'success' : 'warning' }} d-inline-flex align-items-center">
										<i class="ti ti-clock-hour-11 me-1"></i>{{ $workingHours ?? '-' }}
									</span>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="8" class="text-center">No attendance records found for your assigned shifts.</td>
							</tr>
						@endforelse
								</tbody>
							</table>
						</div>
					</div>
				</div>

			</div>

@endsection