@extends('admin.layouts.app')

@section('title')
    {{ __('Outstanding Customer Deposits Report') }}
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
    .bg-liability {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    .bg-holders {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    .bg-received {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }
    .bg-redeemed {
        background-color: rgba(255, 193, 7, 0.15);
        color: #b08500;
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
            <h4 class="mb-1 text-dark fw-bold">{{ __('Outstanding Customer Deposits') }}</h4>
            <p class="text-muted small mb-0">{{ __('Track customer deposit balances, all-time redemptions, and aggregate liability.') }}</p>
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
                <div class="col-md-4">
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

                <div class="col-md-4">
                    <label for="filter_balance" class="form-label small fw-bold text-secondary">{{ __('Balance Filter') }}</label>
                    <select id="filter_balance" class="form-select form-select-sm">
                        <option value="positive" selected>{{ __('Positive Balance Only (> 0)') }}</option>
                        <option value="all">{{ __('All Customers with Deposit History') }}</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="button" id="btn-apply-filter" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-funnel me-1"></i> {{ __('Apply Filter') }}
                    </button>
                    <button type="button" id="btn-reset-filter" class="btn btn-light btn-sm flex-fill border">
                        <i class="bi bi-x-circle me-1"></i> {{ __('Reset') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Outstanding Liability -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm kpi-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="kpi-label text-danger mb-1">{{ __('Total Outstanding Liability') }}</div>
                        <div class="kpi-value text-dark" id="stat-liability">0.00</div>
                        <small class="text-muted">{{ __('Current Available Balance') }}</small>
                    </div>
                    <div class="kpi-icon bg-liability">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Deposit Holders -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm kpi-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="kpi-label text-primary mb-1">{{ __('Active Deposit Holders') }}</div>
                        <div class="kpi-value text-dark" id="stat-holders">0</div>
                        <small class="text-muted">{{ __('Customers with Balance > 0') }}</small>
                    </div>
                    <div class="kpi-icon bg-holders">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Deposits Received -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm kpi-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="kpi-label text-success mb-1">{{ __('Total Received (All-Time)') }}</div>
                        <div class="kpi-value text-dark" id="stat-deposited">0.00</div>
                        <small class="text-muted">{{ __('Deposited across history') }}</small>
                    </div>
                    <div class="kpi-icon bg-received">
                        <i class="bi bi-box-arrow-in-down"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Redeemed -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm kpi-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <div class="kpi-label text-warning mb-1">{{ __('Total Redeemed (All-Time)') }}</div>
                        <div class="kpi-value text-dark" id="stat-used">0.00</div>
                        <small class="text-muted">{{ __('Used against invoices') }}</small>
                    </div>
                    <div class="kpi-icon bg-redeemed">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-table me-2 text-primary"></i> {{ __('Customer Deposit Ledger') }}
            </h6>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0" id="customer-deposits-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>{{ __('Customer Name') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th class="text-end">{{ __('Total Deposited') }}</th>
                            <th class="text-end">{{ __('Total Used') }}</th>
                            <th class="text-center">{{ __('Available Balance') }}</th>
                            <th>{{ __('Last Activity') }}</th>
                            <th style="width: 70px;" class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Load summary KPIs
        function loadStats() {
            var branchId = $('#filter_branch_id').val();
            $.ajax({
                url: "{{ route('report.customer_deposits_stats') }}",
                type: 'GET',
                data: {
                    branch_id: branchId
                },
                success: function(res) {
                    $('#stat-liability').text(parseFloat(res.total_liability || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#stat-holders').text(parseInt(res.customers_count || 0).toLocaleString());
                    $('#stat-deposited').text(parseFloat(res.total_deposited || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#stat-used').text(parseFloat(res.total_used || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                },
                error: function(err) {
                    console.error('Failed to load deposit stats', err);
                }
            });
        }

        // Initialize DataTable
        var table = $('#customer-deposits-table').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            order: [[6, 'desc']], // Sort by Available Balance descending by default
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copy',
                    className: 'btn btn-sm btn-secondary',
                    text: '<i class="bi bi-clipboard me-1"></i> {{ __("Copy") }}'
                },
                {
                    extend: 'excel',
                    className: 'btn btn-sm btn-success',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> {{ __("Excel") }}'
                },
                {
                    extend: 'csv',
                    className: 'btn btn-sm btn-info text-white',
                    text: '<i class="bi bi-filetype-csv me-1"></i> {{ __("CSV") }}'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger',
                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> {{ __("PDF") }}'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark',
                    text: '<i class="bi bi-printer me-1"></i> {{ __("Print") }}'
                }
            ],
            ajax: {
                url: "{{ route('report.customer_deposits_data') }}",
                type: 'GET',
                data: function(d) {
                    d.branch_id = $('#filter_branch_id').val();
                    d.balance_filter = $('#filter_balance').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'customer_phone', name: 'customer_phone' },
                { data: 'branch_name', name: 'branch_name', orderable: false },
                { data: 'total_deposited', name: 'total_deposited', className: 'text-end fw-semibold' },
                { data: 'total_used', name: 'total_used', className: 'text-end text-muted' },
                { data: 'balance_badge', name: 'current_balance', className: 'text-center' },
                { data: 'last_activity', name: 'last_activity' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                emptyTable: "{{ __('No customer deposit records found') }}",
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __("Loading...") }}</span></div>'
            }
        });

        // Initial stats load
        loadStats();

        // Apply filter button
        $('#btn-apply-filter').on('click', function() {
            table.ajax.reload();
            loadStats();
        });

        // Refresh button
        $('#btn-refresh').on('click', function() {
            table.ajax.reload();
            loadStats();
        });

        // Reset filter button
        $('#btn-reset-filter').on('click', function() {
            $('#filter_balance').val('positive');
            @if($canSelectAll ?? true)
                $('#filter_branch_id').val('all');
            @endif
            table.ajax.reload();
            loadStats();
        });

        // Auto reload on filter dropdown change
        $('#filter_branch_id, #filter_balance').on('change', function() {
            table.ajax.reload();
            loadStats();
        });
    });
</script>
@endsection
