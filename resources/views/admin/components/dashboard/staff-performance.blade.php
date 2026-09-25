@if(\App\Traits\AppHelper::perUser('employees.index') || \App\Traits\AppHelper::perUser('reports.index'))
<div class="card border-0 shadow-sm mb-4 staff-performance-card" data-lazy-section="staff">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <h5 class="card-title m-0 fs-6 fw-bold text-dark">
                    <i class="bi bi-trophy-fill text-warning me-2"></i>{{ __('Top Staff Performance') }}
                </h5>
                <span class="text-muted very-small">{{ __('Staff ranking based on service volume, revenue, and earned commissions') }}</span>
            </div>

            @if(\App\Traits\AppHelper::perUser('employees.index'))
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-2xs">
                <span>{{ __('Manage Staff') }}</span>
                <i class="bi bi-arrow-right rtl-flip ms-1"></i>
            </a>
            @endif
        </div>

        {{-- Loading Spinner --}}
        <div x-show="loading.staff" class="text-center py-4">
            <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
            <p class="text-muted small mt-2 mb-0">{{ __('Compiling staff performance metrics...') }}</p>
        </div>

        {{-- Staff Table --}}
        <div x-show="!loading.staff" class="table-responsive">
            <table class="table table-hover align-middle mb-0" aria-label="{{ __('Staff Performance Ranking') }}">
                <thead class="table-light text-muted very-small text-uppercase tracking-wider">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>{{ __('Staff Member') }}</th>
                        <th>{{ __('Branch') }}</th>
                        <th class="text-center">{{ __('Services Done') }}</th>
                        <th class="text-end">{{ __('Revenue Generated') }}</th>
                        <th class="text-end">{{ __('Commission Earned') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(emp, idx) in data.staff" :key="emp.employee_id">
                        <tr>
                            <td>
                                <span class="badge rounded-circle d-inline-flex align-items-center justify-content-center"
                                      :class="{
                                          'bg-warning text-dark': idx === 0,
                                          'bg-secondary text-white': idx === 1,
                                          'bg-light text-dark border': idx > 1
                                      }"
                                      style="width: 24px; height: 24px; font-size: 11px;"
                                      x-text="idx + 1"></span>
                            </td>
                            <td>
                                <strong class="text-dark d-block" x-text="emp.name"></strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-muted border fw-normal" x-text="emp.branch"></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1 rounded-pill" x-text="emp.service_count"></span>
                            </td>
                            <td class="text-end">
                                <strong class="text-dark" x-text="formatCurrency(emp.revenue)"></strong>
                            </td>
                            <td class="text-end">
                                <span class="text-success fw-semibold" x-text="formatCurrency(emp.commission)"></span>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!loading.staff && data.staff.length === 0">
                        <td colspan="6" class="text-center py-4 text-muted small">
                            {{ __('No service orders completed by staff in this period') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
