@extends('admin.layouts.app')

@section('title')
    {{ __('Appointment Conversion Analytics') }}
@endsection

@section('css')
<style>
    .kpi-card {
        border-radius: 10px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: none;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .kpi-value {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .kpi-label {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .bg-conversion {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }
    .bg-bookings {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    .bg-revenue {
        background-color: rgba(111, 66, 193, 0.1);
        color: #6f42c1;
    }
    .bg-cancellations {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    .table thead th {
        vertical-align: middle;
        font-weight: 600;
        background-color: #f8f9fa;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-dark fw-bold">{{ __('Appointment Conversion Analytics') }}</h4>
            <p class="text-muted small mb-0">{{ __('Track booking conversion rates (confirmed to completed), checkout revenue, cancellation and no-show performance.') }}</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-refresh">
                <i class="bi bi-arrow-clockwise me-1"></i> {{ __('Refresh') }}
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter_branch_id" class="form-label small fw-bold text-secondary">{{ __('Branch') }}</label>
                    <select id="filter_branch_id" class="form-select form-select-sm" {{ ! ($canSelectAll ?? true) ? 'disabled' : '' }}>
                        @if($canSelectAll ?? true)
                            <option value="all" {{ ($effectiveBranchId ?? null) === null ? 'selected' : '' }}>{{ __('All Branches') }}</option>
                        @endif
                        @foreach($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}" {{ ($effectiveBranchId ?? null) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="from_date" class="form-label small fw-bold text-secondary">{{ __('From Date') }}</label>
                    <input type="date" id="from_date" class="form-control form-control-sm" value="{{ $fromDate ?? now()->startOfMonth()->toDateString() }}">
                </div>

                <div class="col-md-3">
                    <label for="to_date" class="form-label small fw-bold text-secondary">{{ __('To Date') }}</label>
                    <input type="date" id="to_date" class="form-control form-control-sm" value="{{ $toDate ?? now()->toDateString() }}">
                </div>

                <div class="col-md-3">
                    <button type="button" class="btn btn-primary btn-sm w-100" id="btn-filter">
                        <i class="bi bi-funnel me-1"></i> {{ __('Apply Filter') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards Row -->
    <div class="row g-3 mb-4">
        <!-- 1. Conversion Rate -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-conversion me-3">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <div class="kpi-label text-success">{{ __('Conversion Rate') }}</div>
                        <div class="kpi-value text-dark" id="stat-conversion-rate">0.0%</div>
                        <small class="text-muted"><span id="stat-completed-ratio">0</span> {{ __('completed of confirmed') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Bookings -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-bookings me-3">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="kpi-label text-primary">{{ __('Total Bookings') }}</div>
                        <div class="kpi-value text-dark" id="stat-total-booked">0</div>
                        <small class="text-muted"><span id="stat-confirmed-count">0</span> {{ __('confirmed / active') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Invoiced Revenue -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-revenue me-3">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="kpi-label text-purple" style="color: #6f42c1;">{{ __('Invoiced Revenue') }}</div>
                        <div class="kpi-value text-dark" id="stat-invoiced-revenue">$0.00</div>
                        <small class="text-muted"><span id="stat-invoiced-count">0</span> {{ __('appointments checked out') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Cancellations & No-Shows -->
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-cancellations me-3">
                        <i class="bi bi-x-octagon"></i>
                    </div>
                    <div>
                        <div class="kpi-label text-danger">{{ __('Loss Rate') }}</div>
                        <div class="kpi-value text-dark" id="stat-loss-rate">0.0%</div>
                        <small class="text-muted"><span id="stat-cancelled-count">0</span> {{ __('cancelled') }} / <span id="stat-noshow-count">0</span> {{ __('no-show') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Provider and Service Tables Row -->
    <div class="row g-4">
        <!-- Provider Performance -->
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold">{{ __('Provider Conversion & Checkout Performance') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="table-providers">
                            <thead>
                                <tr>
                                    <th>{{ __('Provider') }}</th>
                                    <th class="text-center">{{ __('Booked') }}</th>
                                    <th class="text-center">{{ __('Completed') }}</th>
                                    <th class="text-center">{{ __('Cancelled') }}</th>
                                    <th class="text-center">{{ __('Conversion') }}</th>
                                    <th class="text-end pe-3">{{ __('Revenue') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">{{ __('Loading data...') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Performance -->
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold">{{ __('Service Conversion Rates') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="table-services">
                            <thead>
                                <tr>
                                    <th>{{ __('Service') }}</th>
                                    <th class="text-center">{{ __('Booked') }}</th>
                                    <th class="text-center">{{ __('Completed') }}</th>
                                    <th class="text-center">{{ __('Rate') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">{{ __('Loading data...') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        function loadStats() {
            const branchVal = $('#filter_branch_id').val();
            const fromDate = $('#from_date').val();
            const toDate = $('#to_date').val();

            const params = {
                branch_id: branchVal === 'all' ? '' : branchVal,
                from_date: fromDate,
                to_date: toDate
            };

            $.ajax({
                url: "{{ route('report.appointment_conversion_stats') }}",
                type: 'GET',
                data: params,
                beforeSend: function() {
                    $('#stat-conversion-rate').text('...');
                },
                success: function(data) {
                    $('#stat-conversion-rate').text(data.confirmed_to_completed_rate + '%');
                    $('#stat-completed-ratio').text(data.completed_count);
                    $('#stat-total-booked').text(data.total_booked);
                    $('#stat-confirmed-count').text(data.confirmed_count + data.checked_in_count + data.in_service_count);
                    $('#stat-invoiced-revenue').text('$' + data.total_revenue);
                    $('#stat-invoiced-count').text(data.invoiced_count);

                    const lossRate = (parseFloat(data.cancellation_rate) + parseFloat(data.no_show_rate)).toFixed(1);
                    $('#stat-loss-rate').text(lossRate + '%');
                    $('#stat-cancelled-count').text(data.cancelled_count);
                    $('#stat-noshow-count').text(data.no_show_count);

                    // Render Provider Table
                    const pTbody = $('#table-providers tbody');
                    pTbody.empty();
                    if (!data.provider_breakdown || data.provider_breakdown.length === 0) {
                        pTbody.append('<tr><td colspan="6" class="text-center py-3 text-muted">{{ __("No appointment records found for this period.") }}</td></tr>');
                    } else {
                        data.provider_breakdown.forEach(function(row) {
                            const badgeClass = row.conversion_rate >= 80 ? 'bg-success' : (row.conversion_rate >= 50 ? 'bg-primary' : 'bg-warning text-dark');
                            pTbody.append(`
                                <tr>
                                    <td class="fw-semibold">${row.provider_name}</td>
                                    <td class="text-center">${row.total_booked}</td>
                                    <td class="text-center text-success fw-semibold">${row.completed}</td>
                                    <td class="text-center text-danger">${row.cancelled}</td>
                                    <td class="text-center"><span class="badge ${badgeClass}">${row.conversion_rate}%</span></td>
                                    <td class="text-end pe-3 fw-bold">$${row.revenue}</td>
                                </tr>
                            `);
                        });
                    }

                    // Render Service Table
                    const sTbody = $('#table-services tbody');
                    sTbody.empty();
                    if (!data.service_breakdown || data.service_breakdown.length === 0) {
                        sTbody.append('<tr><td colspan="4" class="text-center py-3 text-muted">{{ __("No service records found.") }}</td></tr>');
                    } else {
                        data.service_breakdown.forEach(function(row) {
                            const badgeClass = row.conversion_rate >= 80 ? 'bg-success' : (row.conversion_rate >= 50 ? 'bg-primary' : 'bg-warning text-dark');
                            sTbody.append(`
                                <tr>
                                    <td class="fw-semibold">${row.service_name}</td>
                                    <td class="text-center">${row.total_booked}</td>
                                    <td class="text-center text-success">${row.completed}</td>
                                    <td class="text-center"><span class="badge ${badgeClass}">${row.conversion_rate}%</span></td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function() {
                    $('#stat-conversion-rate').text('0.0%');
                }
            });
        }

        $('#btn-filter, #btn-refresh').on('click', function() {
            loadStats();
        });

        $('#filter_branch_id').on('change', function() {
            loadStats();
        });

        loadStats();
    });
</script>
@endsection
