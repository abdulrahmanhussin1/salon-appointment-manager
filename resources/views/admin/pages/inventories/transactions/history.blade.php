@extends('admin.layouts.app')
@section('title')
    {{ __('Stock Adjustment History') }}
@endsection
@section('content')
    <x-breadcrumb pageName="Stock Adjustment History">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('inventories.index') }}">{{ __('Inventories') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="true">
            {{ __('Stock Adjustment History') }}
        </x-breadcrumb-item>
    </x-breadcrumb>

    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>{{ __('Stock Adjustment Audit History') }}</h4>
                <p class="text-muted small mb-0">{{ __('Immutable log of all manual stock corrections, physical audit reconciliations, and shrinkage records.') }}</p>
            </div>
            <div>
                <a href="{{ route('inventory_transactions.adjustView') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>{{ __('New Stock Adjustment') }}
                </a>
            </div>
        </div>

        @include('admin.layouts.alerts')

        <!-- Filter Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-funnel me-2"></i>{{ __('Filter Adjustments') }}</h6>
            </div>
            <div class="card-body">
                <form id="filter-form" class="row g-3 align-items-end">
                    @if($canSelectAll)
                        <div class="col-md-3">
                            <label for="filter_branch_id" class="form-label small fw-semibold">{{ __('Branch') }}</label>
                            <select id="filter_branch_id" name="branch_id" class="form-select form-select-sm">
                                <option value="all">{{ __('All Branches') }}</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ $effectiveBranchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" id="filter_branch_id" name="branch_id" value="{{ $effectiveBranchId }}">
                    @endif

                    <div class="col-md-3">
                        <label for="filter_inventory_id" class="form-label small fw-semibold">{{ __('Inventory') }}</label>
                        <select id="filter_inventory_id" name="inventory_id" class="form-select form-select-sm">
                            <option value="">{{ __('All Inventories') }}</option>
                            @foreach($inventories as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_adjustment_type" class="form-label small fw-semibold">{{ __('Direction') }}</label>
                        <select id="filter_adjustment_type" name="adjustment_type" class="form-select form-select-sm">
                            <option value="">{{ __('All Directions') }}</option>
                            <option value="increase">{{ __('Increase (+)') }}</option>
                            <option value="decrease">{{ __('Decrease (-)') }}</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_adjustment_reason" class="form-label small fw-semibold">{{ __('Reason') }}</label>
                        <select id="filter_adjustment_reason" name="adjustment_reason" class="form-select form-select-sm">
                            <option value="">{{ __('All Reasons') }}</option>
                            <option value="count_correction">{{ __('Count Correction') }}</option>
                            <option value="damage">{{ __('Damaged') }}</option>
                            <option value="waste">{{ __('Waste / Expired') }}</option>
                            <option value="theft">{{ __('Shrinkage / Theft') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_from_date" class="form-label small fw-semibold">{{ __('From Date') }}</label>
                        <input type="date" id="filter_from_date" name="from_date" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label for="filter_to_date" class="form-label small fw-semibold">{{ __('To Date') }}</label>
                        <input type="date" id="filter_to_date" name="to_date" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="button" id="btn-apply-filter" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-search me-1"></i>{{ __('Filter') }}
                        </button>
                        <button type="button" id="btn-reset-filter" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('Reset') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="adjustments-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Date & Time') }}</th>
                                <th>{{ __('Inventory') }}</th>
                                <th>{{ __('Branch') }}</th>
                                <th class="text-center">{{ __('Direction') }}</th>
                                <th>{{ __('Reason') }}</th>
                                <th>{{ __('Adjusted Products') }}</th>
                                <th>{{ __('Notes') }}</th>
                                <th>{{ __('Adjusted By') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        const table = $('#adjustments-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('inventory_transactions.history_data') }}",
                data: function(d) {
                    d.branch_id = $('#filter_branch_id').val();
                    d.inventory_id = $('#filter_inventory_id').val();
                    d.adjustment_type = $('#filter_adjustment_type').val();
                    d.adjustment_reason = $('#filter_adjustment_reason').val();
                    d.from_date = $('#filter_from_date').val();
                    d.to_date = $('#filter_to_date').val();
                }
            },
            columns: [
                { data: 'id', name: 'id', width: '50px' },
                { data: 'formatted_date', name: 'created_at', width: '130px' },
                { data: 'inventory_name', name: 'inventory_name' },
                { data: 'branch_name', name: 'branch_name' },
                { data: 'type_badge', name: 'adjustment_type', className: 'text-center', width: '110px' },
                { data: 'reason_label', name: 'adjustment_reason' },
                { data: 'products_summary', name: 'products_summary' },
                { data: 'notes', name: 'notes', defaultContent: '—' },
                { data: 'user_name', name: 'user_name' }
            ],
            order: [[0, 'desc']],
            pageLength: 25
        });

        $('#btn-apply-filter').on('click', function() {
            table.draw();
        });

        $('#btn-reset-filter').on('click', function() {
            $('#filter-form')[0].reset();
            table.draw();
        });

        $('#filter_branch_id, #filter_inventory_id, #filter_adjustment_type, #filter_adjustment_reason').on('change', function() {
            table.draw();
        });
    });
</script>
@endsection
