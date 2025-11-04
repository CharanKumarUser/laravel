{{-- Template: Reports Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Reports')
@push('styles') {{-- Header Styles--}} @endpush
@push('pre-scripts') {{-- Header Scripts--}} @endpush
@push('scripts') {{-- Body Scripts--}} @endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Reports</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/attendance-management') }}">Attendance Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Reports</a></li>
                </ol>
            </nav>
        </div>
        <div></div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="live-time-container head-icons">
                    <span class="live-time-icon me-2"><i class="fa-thin fa-clock"></i></span>
                    <div class="live-time"></div>
                </div>
            <div class="ms-2 head-icons">
                <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-12">
        {{--************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (START) <<<                                  *
        *                                                                                                  *
        ************************************************************************************************--}}
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
									<div class="badge badge-md badge-primary mb-3">Production :  {{ $data['kpis']['today_working_hm'] ?? '-' }}</div>
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
											<span class="badge bg-success-subtle text-success"><i class="ti ti-clock-hour-9 me-1"></i>{{ $data['kpis']['today_working_hm'] ?? '-' }}</span>
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
									<h2 class="mb-2">{{ $data['kpis']['today_working_hm'] ?? '00h 00m' }} / <span class="fs-20 text-gray-5"> 9h</span></h2>
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
									<h2 class="mb-2">{{ $data['kpis']['week_working_hm'] ?? '00h 00m' }} / <span class="fs-20 text-gray-5"> 40h</span></h2>
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
									<h2 class="mb-2">{{ $data['kpis']['month_working_hm'] ?? '00h 00m' }} / <span class="fs-20 text-gray-5"> 98h</span></h2>
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
									<h2 class="mb-2">{{ $data['kpis']['month_overtime_hm'] ?? '00h 00m' }} / <span class="fs-20 text-gray-5"> 28h</span></h2>
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
                                                    <h3>{{ $data['kpis']['today_working_hm'] ?? '-' }}</h3>
                                                </div>
                                            </div>
											<div class="col-xl-3">
												<div class="mb-3">
                                                    <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-success me-1"></i>Productive Hours</p>
									<h3>{{ $data['kpis']['today_working_hm'] ?? '-' }}</h3>
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
												$DAY_SEC = 24 * 60 * 60; // 86400
												$startSec = 0; // 00:00:00
												$totalSec = $DAY_SEC;
												$todayStr = now()->toDateString();
												function timeToPercentFullDay($t) {
													if (empty($t)) return null;
													$sec = \Carbon\Carbon::createFromFormat('H:i:s', $t)->secondsSinceMidnight();
													return ($sec / 86400) * 100;
												}
											@endphp
											{{-- Hour tick bar --}}
											<div class="position-relative w-100 bg-transparent-dark rounded mb-1" style="height: 20px;">
												@for($h=0; $h<=24; $h+=3)
													@php $pct = ($h * 3600) / 86400 * 100; @endphp
													<div class="position-absolute" style="left: {{ $pct }}%; top: 0; bottom: 0; width: 1px; background: rgba(255,255,255,0.15);"></div>
												@endfor
											</div>
											{{-- One lane per assigned shift for today --}}
											@php
												$todayRows = collect($data['attendance'] ?? [])->filter(fn($r) => ($r->attendance_date ?? '') === $todayStr);
												$byShift = $todayRows->groupBy(fn($r) => $r->shift_id ?? 'no-shift');
											@endphp
											@foreach($byShift as $shiftId => $rows)
												@php $shiftName = $rows->first()->shift_name ?? 'Shift'; @endphp
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
											@endforeach
											<div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
												@for($h=0; $h<=24; $h+=3)
													<span class="fs-10">{{ sprintf('%02d:00', $h % 24) }}</span>
												@endfor
											</div>
												
											</div>
											{{-- Removed old static hour labels to avoid duplicates --}}
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
									@if(($row->status ?? '') === 'present')
										<span class="badge badge-success-transparent d-inline-flex align-items-center">
											<i class="ti ti-point-filled me-1"></i>Present
										</span>
									@else
										<span class="badge badge-danger-transparent d-inline-flex align-items-center">
											<i class="ti ti-point-filled me-1"></i>Absent
										</span>
									@endif
								</td>
								<td>
									{{ $row->check_out ? \Carbon\Carbon::createFromFormat('H:i:s', $row->check_out)->format('h:i A') : '-' }}
								</td>
								<td>{{ $row->late_in ?? '-' }}</td>
								<td>
									{{ $row->overtime ?? '-' }}
								</td>
								<td>
									<span class="badge badge-{{ ($row->working_hours ?? '') >= '08:00:00' ? 'success' : 'danger' }} d-inline-flex align-items-center">
										<i class="ti ti-clock-hour-11 me-1"></i>{{ $row->working_hours ?? '-' }}
									</span>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="9" class="text-center">No attendance records found for your assigned shifts.</td>
							</tr>
						@endforelse
								</tbody>
							</table>
						</div>
					</div>
				</div>

			</div>

@endsection