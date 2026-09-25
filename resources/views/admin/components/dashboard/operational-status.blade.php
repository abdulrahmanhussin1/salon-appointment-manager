<div class="row g-3 mb-4 operational-section">

    {{-- Left: Donut Status Chart --}}
    <div class="col-12 col-xl-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="card-title m-0 fs-6 fw-bold text-dark">
                        <i class="bi bi-pie-chart-fill text-primary me-2"></i>{{ __('Appointment Status') }}
                    </h5>
                    <span class="badge bg-light text-muted border" x-text="data.summary?.appointments?.total + ' ' + '{{ __('Total') }}'">0</span>
                </div>

                {{-- Chart Loading --}}
                <div x-show="loading.appointments" class="text-center py-5">
                    <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">{{ __('Loading status distribution...') }}</p>
                </div>

                {{-- Chart Container --}}
                <div x-show="!loading.appointments">
                    <div id="appointment-status-chart" style="min-height: 260px;"></div>
                </div>

                {{-- Key Stats Summary --}}
                <div class="row g-2 mt-2 pt-2 border-top text-center" x-show="!loading.appointments">
                    <div class="col-4">
                        <span class="d-block text-muted very-small">{{ __('Completed') }}</span>
                        <strong class="text-success fs-6" x-text="data.statusBreakdown?.completed || 0">0</strong>
                    </div>
                    <div class="col-4">
                        <span class="d-block text-muted very-small">{{ __('In Progress') }}</span>
                        <strong class="text-warning fs-6" x-text="(data.statusBreakdown?.checked_in || 0) + (data.statusBreakdown?.in_service || 0)">0</strong>
                    </div>
                    <div class="col-4">
                        <span class="d-block text-muted very-small">{{ __('Pending') }}</span>
                        <strong class="text-primary fs-6" x-text="data.statusBreakdown?.requested || 0">0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Today's Appointments Operational Table --}}
    <div class="col-12 col-xl-8">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
                    <div>
                        <h5 class="card-title m-0 fs-6 fw-bold text-dark">
                            <i class="bi bi-calendar-event text-primary me-2"></i>{{ __("Today's Appointments") }}
                        </h5>
                        <span class="text-muted very-small">{{ __('Real-time operational workflow & check-in') }}</span>
                    </div>

                    {{-- Table Status Filter Buttons --}}
                    <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border shadow-2xs">
                        <button type="button" class="btn btn-sm rounded-pill px-2 py-0 border-0"
                                :class="appointmentTableFilter === 'all' ? 'bg-white shadow-xs fw-bold text-dark' : 'text-muted'"
                                @click="appointmentTableFilter = 'all'">
                            {{ __('All') }}
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-2 py-0 border-0"
                                :class="appointmentTableFilter === 'requested' ? 'bg-white shadow-xs fw-bold text-primary' : 'text-muted'"
                                @click="appointmentTableFilter = 'requested'">
                            {{ __('Requested') }}
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-2 py-0 border-0"
                                :class="appointmentTableFilter === 'confirmed' ? 'bg-white shadow-xs fw-bold text-info' : 'text-muted'"
                                @click="appointmentTableFilter = 'confirmed'">
                            {{ __('Confirmed') }}
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-2 py-0 border-0"
                                :class="appointmentTableFilter === 'in_service' ? 'bg-white shadow-xs fw-bold text-warning' : 'text-muted'"
                                @click="appointmentTableFilter = 'in_service'">
                            {{ __('In Service') }}
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-2 py-0 border-0"
                                :class="appointmentTableFilter === 'completed' ? 'bg-white shadow-xs fw-bold text-success' : 'text-muted'"
                                @click="appointmentTableFilter = 'completed'">
                            {{ __('Completed') }}
                        </button>
                    </div>
                </div>

                {{-- Table Loading --}}
                <div x-show="loading.appointments" class="text-center py-5">
                    <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">{{ __('Loading appointments...') }}</p>
                </div>

                {{-- Table Content --}}
                <div x-show="!loading.appointments" class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" aria-label="{{ __("Today's Appointments") }}">
                        <thead class="table-light text-muted very-small text-uppercase tracking-wider sticky-top bg-light">
                            <tr>
                                <th>{{ __('Time') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Service') }}</th>
                                <th class="d-none d-md-table-cell">{{ __('Staff') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="appt in filteredAppointments" :key="appt.id">
                                <tr>
                                    {{-- Time --}}
                                    <td class="text-nowrap">
                                        <strong class="text-dark d-block" x-text="appt.start_time"></strong>
                                        <small class="text-muted very-small" x-text="appt.duration_minutes + ' min'"></small>
                                    </td>

                                    {{-- Customer --}}
                                    <td>
                                        <span class="fw-semibold text-dark d-block text-truncate" style="max-width: 130px;" x-text="appt.customer_name"></span>
                                        <small class="text-muted very-small" x-text="appt.customer_phone || ''"></small>
                                    </td>

                                    {{-- Service --}}
                                    <td>
                                        <span class="badge bg-light text-dark border fw-normal" x-text="appt.service_name"></span>
                                    </td>

                                    {{-- Staff --}}
                                    <td class="d-none d-md-table-cell text-muted small" x-text="appt.provider_name"></td>

                                    {{-- Status Badge --}}
                                    <td>
                                        <span :class="{
                                            'badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25': appt.status === 'requested',
                                            'badge bg-info bg-opacity-10 text-info border border-info border-opacity-25': appt.status === 'confirmed',
                                            'badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25': appt.status === 'checked_in',
                                            'badge bg-warning text-dark': appt.status === 'in_service',
                                            'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25': appt.status === 'completed',
                                            'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25': appt.status === 'cancelled',
                                            'badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25': appt.status === 'no_show',
                                        }" class="px-2 py-1 rounded-pill" x-text="appt.status_label"></span>
                                    </td>

                                    {{-- Inline Actions --}}
                                    <td class="text-end text-nowrap">
                                        {{-- Confirm (for requested) --}}
                                        <template x-if="appt.can_confirm">
                                            <button type="button"
                                                    class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1"
                                                    @click="confirmAppointment(appt.id)"
                                                    title="{{ __('Confirm booking') }}">
                                                <i class="bi bi-check-lg me-1"></i>{{ __('Confirm') }}
                                            </button>
                                        </template>

                                        {{-- Check In (for confirmed) --}}
                                        <template x-if="appt.can_check_in">
                                            <button type="button"
                                                    class="btn btn-xs btn-outline-info rounded-pill px-2 py-1"
                                                    @click="checkInAppointment(appt.id)"
                                                    title="{{ __('Check in customer') }}">
                                                <i class="bi bi-box-arrow-in-right me-1"></i>{{ __('Check In') }}
                                            </button>
                                        </template>

                                        {{-- Start Service (for checked in) --}}
                                        <template x-if="appt.can_start">
                                            <button type="button"
                                                    class="btn btn-xs btn-warning rounded-pill px-2 py-1 text-dark"
                                                    @click="startServiceAppointment(appt.id)"
                                                    title="{{ __('Start service') }}">
                                                <i class="bi bi-play-fill me-1"></i>{{ __('Start') }}
                                            </button>
                                        </template>

                                        {{-- Complete (for checked in or in service) --}}
                                        <template x-if="appt.can_complete">
                                            <button type="button"
                                                    class="btn btn-xs btn-success rounded-pill px-2 py-1"
                                                    @click="completeAppointment(appt.id)"
                                                    title="{{ __('Complete appointment') }}">
                                                <i class="bi bi-check2-circle me-1"></i>{{ __('Complete') }}
                                            </button>
                                        </template>

                                        {{-- No Show --}}
                                        <template x-if="appt.can_no_show">
                                            <button type="button"
                                                    class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1 ms-1"
                                                    @click="noShowAppointment(appt.id)"
                                                    title="{{ __('Mark as No Show') }}">
                                                {{ __('No Show') }}
                                            </button>
                                        </template>

                                        {{-- Cancel --}}
                                        <template x-if="appt.can_cancel">
                                            <button type="button"
                                                    class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1 ms-1"
                                                    @click="cancelAppointment(appt.id)"
                                                    title="{{ __('Cancel appointment') }}">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                            </template>

                            {{-- Empty Table State --}}
                            <tr x-show="!loading.appointments && filteredAppointments.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <i class="bi bi-calendar-x fs-1 text-muted mb-2"></i>
                                        <p class="mb-1 fw-medium">{{ __('No appointments found for the selected view') }}</p>
                                        <small class="text-muted">{{ __('Use Quick Actions to book a new client appointment') }}</small>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
