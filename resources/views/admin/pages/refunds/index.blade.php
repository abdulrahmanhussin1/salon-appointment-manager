@extends('admin.layouts.app')
@section('title')
    {{ __('Refunds & Product Returns') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Refunds & Returns">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('sales_invoices.index') }}">{{ __('Sales Invoices') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Refunds & Returns') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0 text-primary"><i class="bi bi-arrow-counterclockwise me-2"></i>{{ __('Refunds & Returns Audit Trail') }}</h4>
                <p class="text-muted small mb-0">{{ __('Track customer refunds, product returns, and employee commission reversals') }}</p>
            </div>
            <div>
                @if (App\Traits\AppHelper::perUser('refunds.create') || App\Traits\AppHelper::perUser('sales_invoices.create'))
                    <a href="{{ route('refunds.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('Issue Refund / Return') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3">
                <form id="refundFilterForm" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">{{ __('From Date') }}</label>
                        <input type="date" id="start_date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date', now()->subDays(30)->toDateString()) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">{{ __('To Date') }}</label>
                        <input type="date" id="end_date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date', now()->toDateString()) }}">
                    </div>
                    @if ($canSelectAll)
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">{{ __('Branch') }}</label>
                            <select id="branch_id" name="branch_id" class="form-select form-select-sm">
                                <option value="">{{ __('All Branches') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $effectiveBranchId == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">{{ __('Refund Method') }}</label>
                        <select id="refund_method" name="refund_method" class="form-select form-select-sm">
                            <option value="">{{ __('All Methods') }}</option>
                            <option value="cash">{{ __('Cash') }}</option>
                            <option value="deposit">{{ __('Deposit Credit') }}</option>
                            <option value="card">{{ __('Card') }}</option>
                            <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="button" id="btnFilter" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-funnel"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @include('admin.layouts.alerts')

        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="refundsTable" class="table table-sm table-hover align-middle w-100 fs--1">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Refund #') }}</th>
                                <th>{{ __('Invoice') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Branch') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Refund Amount') }}</th>
                                <th>{{ __('Commission Reversed') }}</th>
                                <th>{{ __('Reason') }}</th>
                                <th>{{ __('Processed By') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            var table = $('#refundsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('refunds.index') }}",
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.branch_id = $('#branch_id').val();
                        d.refund_method = $('#refund_method').val();
                    }
                },
                columns: [
                    { data: 'refund_number', name: 'refund_number', className: 'fw-bold text-nowrap' },
                    { data: 'invoice_link', name: 'sales_invoice_id', orderable: false, searchable: false },
                    { data: 'customer_name', name: 'customer.name' },
                    { data: 'branch_name', name: 'branch.name' },
                    { data: 'refund_date', name: 'refund_date', className: 'text-nowrap' },
                    { data: 'method_badge', name: 'refund_method', orderable: false, searchable: false },
                    { data: 'total_refund_amount', name: 'total_refund_amount', className: 'fw-bold text-danger text-nowrap' },
                    { data: 'commission_reversed_amount', name: 'commission_reversed_amount', className: 'text-nowrap' },
                    { data: 'reason', name: 'reason' },
                    { data: 'processed_by', name: 'createdBy.name' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    emptyTable: "{{ __('No refunds recorded yet.') }}"
                }
            });

            $('#btnFilter').on('click', function() {
                table.draw();
            });

            $('#start_date, #end_date, #branch_id, #refund_method').on('change', function() {
                table.draw();
            });
        });
    </script>
@endsection
